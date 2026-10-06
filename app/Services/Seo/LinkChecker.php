<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Models\Redirect;
use App\Models\SeoLinkCheck;
use App\Services\PublicNavigation;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Finds broken links in navigation, footer, page content, related content,
 * calls to action, redirect targets and public media.
 *
 * Internal links are resolved through the HTTP kernel (no network). External
 * links are checked only when enabled, with a timeout, a per-run cap and a
 * delay between requests.
 */
final class LinkChecker
{
    /** @var array<string, array{result: string, status: int|null}> */
    private array $internalCache = [];

    private int $externalChecked = 0;

    public function __construct(
        private readonly PageInspector $inspector,
        private readonly SitemapBuilder $sitemaps,
        private readonly CanonicalUrlBuilder $urls,
        private readonly PublicNavigation $navigation,
        private readonly EffectiveSettings $settings,
    ) {}

    /** @return array{checked: int, broken: int, redirected: int, external_checked: int} */
    public function run(bool $includeExternal = false): array
    {
        $includeExternal = $includeExternal || (bool) config('impact.seo.link_check.external_enabled', false);
        $seen = [];
        $summary = ['checked' => 0, 'broken' => 0, 'redirected' => 0, 'external_checked' => 0];
        $record = function (string $url, string $sourceUrl, string $sourceLabel, ?string $text, bool $structural) use (&$seen, &$summary, $includeExternal): void {
            $check = $this->check($url, $includeExternal);
            if ($check === null) {
                return;
            }
            $summary['checked']++;
            $summary['broken'] += in_array($check['result'], ['broken', 'error'], true) ? 1 : 0;
            $summary['redirected'] += $check['result'] === 'redirect' ? 1 : 0;
            $fingerprint = hash('sha256', $url.'|'.$sourceUrl.'|'.$sourceLabel);
            $seen[$fingerprint] = true;
            $external = ! $this->isInternal($url);
            $severity = match (true) {
                $check['result'] === 'ok' => 'information',
                $check['result'] === 'redirect' => 'information',
                $structural && ! $external => 'blocking',
                default => 'warning',
            };

            $existing = SeoLinkCheck::query()->where('fingerprint', $fingerprint)->first();
            $link = $existing ?? new SeoLinkCheck(['fingerprint' => $fingerprint, 'first_detected_at' => now('UTC')]);
            $link->forceFill([
                'url' => mb_substr($url, 0, 1024),
                'source_url' => mb_substr($sourceUrl, 0, 1024),
                'source_label' => mb_substr($sourceLabel, 0, 160),
                'link_text' => $text === null ? null : mb_substr($text, 0, 255),
                'external' => $external,
                'status_code' => $check['status'],
                'result' => $check['result'],
                'severity' => $severity,
                'resolution_status' => in_array($check['result'], ['broken', 'error'], true) ? 'open' : 'resolved',
                'last_checked_at' => now('UTC'),
            ])->save();
        };

        $home = $this->urls->url('/');
        foreach (['primary', 'footer_explore', 'footer_engage', 'footer_legal'] as $location) {
            foreach ($this->navigation->location($location, 'en') as $item) {
                $record((string) $item['url'], $home, 'Navigation: '.str_replace('_', ' ', $location), (string) $item['label'], true);
                foreach ($item['children'] ?? [] as $child) {
                    $record((string) $child['url'], $home, 'Navigation: '.str_replace('_', ' ', $location), (string) $child['label'], true);
                }
            }
        }

        foreach (collect($this->sitemaps->all())->flatten(1) as $entry) {
            $path = $this->urls->normalizePath((string) parse_url($entry->loc, PHP_URL_PATH));
            $page = $this->inspector->inspect($path);
            // Shared navigation is identical on every page; record it once.
            foreach ($this->linksIn($page['props'], $path === '/') as [$href, $text, $key]) {
                $record($href, $entry->loc, $key === 'navigation' ? 'Mega menu' : 'Page content', $text, $key === 'navigation');
            }
        }

        Redirect::query()->where('enabled', true)->where('status_code', '!=', 410)->get()
            ->each(fn (Redirect $redirect) => $record((string) $redirect->destination_url, $this->urls->url($redirect->source_path), 'Redirect target', null, true));

        foreach (['branding.logo_url' => 'Logo', 'branding.favicon_url' => 'Favicon', 'branding.social_image_url' => 'Default social image'] as $key => $label) {
            $reference = $this->settings->mediaReference($key);
            if ($reference !== null) {
                $record($reference, $home, 'Public media: '.$label, $label, true);
            }
        }

        // Links not seen in this run no longer exist on the site.
        SeoLinkCheck::query()->where('resolution_status', 'open')->get()
            ->reject(static fn (SeoLinkCheck $link): bool => isset($seen[$link->fingerprint]))
            ->each(static fn (SeoLinkCheck $link) => $link->forceFill(['resolution_status' => 'resolved'])->save());

        $summary['external_checked'] = $this->externalChecked;

        return $summary;
    }

    /**
     * Walks page props for link targets with their visible text.
     *
     * @param  array<string, mixed>  $props
     * @return list<array{0: string, 1: string|null, 2: string}>
     */
    private function linksIn(array $props, bool $includeNavigation): array
    {
        unset($props['seo'], $props['site'], $props['ui'], $props['flash']);
        if (! $includeNavigation) {
            unset($props['navigation']);
        }
        $links = [];
        $walk = function (mixed $value, string $root) use (&$walk, &$links): void {
            if (! is_array($value)) {
                return;
            }
            foreach (['href', 'url', 'emptyHref', 'contactHref'] as $key) {
                if (isset($value[$key]) && is_string($value[$key]) && $value[$key] !== '' && $value[$key] !== '#') {
                    $links[] = [$value[$key], $value['label'] ?? $value['name'] ?? $value['title'] ?? null, $root];
                }
            }
            foreach ($value as $key => $child) {
                if (is_array($child)) {
                    $walk($child, $root === '' ? (string) $key : $root);
                } elseif (is_string($child) && preg_match_all('/href="([^"]+)"/', $child, $matches) > 0) {
                    foreach ($matches[1] as $href) {
                        $links[] = [html_entity_decode($href), null, $root];
                    }
                }
            }
        };
        $walk($props, '');

        return array_values(array_filter($links, static fn (array $link): bool => ! str_starts_with($link[0], 'mailto:') && ! str_starts_with($link[0], 'tel:')));
    }

    /** @return array{result: string, status: int|null}|null */
    private function check(string $url, bool $includeExternal): ?array
    {
        if ($this->isInternal($url)) {
            $path = (string) parse_url($url, PHP_URL_PATH) ?: '/';
            if (str_starts_with($path, '/storage/') || str_starts_with($path, '/images/') || str_starts_with($path, '/build/')) {
                return $this->publicFile($path);
            }

            return $this->internalCache[$path] ??= $this->internal($path);
        }

        if (! $includeExternal || $this->externalChecked >= (int) config('impact.seo.link_check.external_limit', 50)) {
            return null;
        }
        $this->externalChecked++;
        usleep(max(0, (int) config('impact.seo.link_check.delay_milliseconds', 250)) * 1000);
        try {
            $response = Http::timeout((int) config('impact.seo.link_check.timeout_seconds', 5))
                ->withUserAgent('ImpactSeoLinkCheck/1.0')
                ->withOptions(['allow_redirects' => ['max' => 3]])
                ->head($url);
            if ($response->status() === 405) {
                $response = Http::timeout((int) config('impact.seo.link_check.timeout_seconds', 5))->withUserAgent('ImpactSeoLinkCheck/1.0')->get($url);
            }

            return ['result' => $response->status() < 400 ? 'ok' : 'broken', 'status' => $response->status()];
        } catch (Throwable) {
            return ['result' => 'error', 'status' => null];
        }
    }

    /** @return array{result: string, status: int|null} */
    private function internal(string $path): array
    {
        $page = $this->inspector->inspect($this->urls->normalizePath($path));

        return match (true) {
            $page['status'] === 200 => ['result' => 'ok', 'status' => 200],
            in_array($page['status'], [301, 302, 307, 308], true) => ['result' => 'redirect', 'status' => $page['status']],
            default => ['result' => 'broken', 'status' => $page['status']],
        };
    }

    /** @return array{result: string, status: int|null} */
    private function publicFile(string $path): array
    {
        $exists = str_starts_with($path, '/storage/')
            ? Storage::disk((string) config('impact.files.public_disk', 'public'))->exists(substr($path, strlen('/storage/')))
            : is_file(public_path(ltrim($path, '/')));

        return $exists ? ['result' => 'ok', 'status' => 200] : ['result' => 'broken', 'status' => 404];
    }

    private function isInternal(string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, [
            strtolower((string) parse_url($this->urls->url('/'), PHP_URL_HOST)),
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            '127.0.0.1',
            'localhost',
        ], true);
    }
}
