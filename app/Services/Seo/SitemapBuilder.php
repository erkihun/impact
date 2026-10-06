<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Data\Seo\SitemapEntry;
use App\Enums\Seo\PublicResourceType;
use App\Enums\Seo\RobotsDirective;
use App\Models\Event;
use App\Models\Redirect;
use App\Models\SeoMetadata as SeoOverride;
use App\Models\Vacancy;
use App\Queries\Seo\PublicResourceQuery;
use App\Support\Settings\EffectiveSettings;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToMoveFile;

/**
 * English-only XML sitemaps: /sitemap.xml indexes one child sitemap per
 * content family. Only current, published, indexable canonical URLs are
 * listed; drafts, previews, search, filtered URLs, redirect sources and
 * retired URLs never appear.
 */
final readonly class SitemapBuilder
{
    /** Static pages: route name => editor subject key. */
    private const PAGES = [
        'home' => 'home',
        'about.show' => 'about',
        'services.index' => 'services.index',
        'industries.index' => 'industries.index',
        'experts.index' => 'experts.index',
        'case-studies.index' => 'case-studies.index',
        'insights.index' => 'insights.index',
        'events.index' => 'events.index',
        'careers.index' => 'careers.index',
        'consultation.create' => 'consultation',
        'rfp.create' => 'rfp',
        'contact.create' => 'contact',
        'legal.privacy' => 'legal.privacy',
        'legal.terms' => 'legal.terms',
        'legal.cookies' => 'legal.cookies',
        'legal.accessibility' => 'legal.accessibility',
    ];

    /** Pages that exist only while their feature is switched on. */
    private const FEATURE_PAGES = [
        'consultation.create' => 'engagement.consultation_form_enabled',
        'rfp.create' => 'engagement.rfp_form_enabled',
        'contact.create' => 'engagement.contact_form_enabled',
    ];

    public function __construct(
        private PublicResourceQuery $resources,
        private CanonicalUrlBuilder $urls,
        private PublicUrlGenerator $publicUrls,
        private SeoSettings $settings,
        private EffectiveSettings $effective,
    ) {}

    /**
     * Static public pages: route name => SEO subject key.
     *
     * @return array<string, string>
     */
    public static function pageKeys(): array
    {
        return self::PAGES;
    }

    /** @return list<string> */
    public function segments(): array
    {
        return ['pages', ...array_map(static fn (PublicResourceType $type): string => $type->segment(), PublicResourceType::cases())];
    }

    /** @return Collection<int, SitemapEntry> */
    public function entries(string $segment): Collection
    {
        if (! in_array($segment, $this->segments(), true)) {
            return collect();
        }

        $redirected = Redirect::query()->where('enabled', true)->pluck('source_path')->flip();
        $entries = $segment === 'pages'
            ? $this->pageEntries()
            : $this->resourceEntries(PublicResourceType::fromSegment($segment) ?? PublicResourceType::Service);

        return $entries
            ->reject(fn (SitemapEntry $entry): bool => $redirected->has($this->urls->normalizePath((string) parse_url($entry->loc, PHP_URL_PATH))))
            ->unique('loc')
            ->values();
    }

    /** @return array<string, Collection<int, SitemapEntry>> */
    public function all(): array
    {
        $all = [];
        foreach ($this->segments() as $segment) {
            $all[$segment] = $this->entries($segment);
        }

        return $all;
    }

    public function urlsetXml(Collection $entries): string
    {
        $body = $entries->map(fn (SitemapEntry $entry): string => '<url><loc>'.$this->xml($entry->loc).'</loc>'
            .($entry->lastModified ? '<lastmod>'.$entry->lastModified->toAtomString().'</lastmod>' : '')
            .'</url>')->implode("\n");

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$body}\n</urlset>\n";
    }

    /** @param  array<string, CarbonInterface|null>  $segments */
    public function indexXml(array $segments): string
    {
        $body = collect($segments)->map(fn (?CarbonInterface $lastModified, string $segment): string => '<sitemap><loc>'
            .$this->xml($this->urls->forRoute('sitemap.segment', ['segment' => $segment])).'</loc>'
            .($lastModified ? '<lastmod>'.$lastModified->toAtomString().'</lastmod>' : '')
            .'</sitemap>')->implode("\n");

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$body}\n</sitemapindex>\n";
    }

    /**
     * Regenerates every file under a lock and swaps each one in atomically.
     *
     * @return array<string, int> URL count per non-empty segment
     */
    public function generate(): array
    {
        $lock = Cache::lock('seo:sitemap-generate', 120);

        try {
            return $lock->block(30, function (): array {
                $disk = Storage::disk((string) config('impact.seo.sitemap_disk', 'local'));
                $directory = (string) config('impact.seo.sitemap_directory', 'seo/sitemaps');
                $counts = [];
                $index = [];

                foreach ($this->all() as $segment => $entries) {
                    if ($entries->isEmpty()) {
                        $disk->delete("{$directory}/{$segment}.xml");

                        continue;
                    }
                    $this->atomicPut($disk, "{$directory}/{$segment}.xml", $this->urlsetXml($entries));
                    $counts[$segment] = $entries->count();
                    $index[$segment] = $entries->map(fn (SitemapEntry $entry): ?CarbonInterface => $entry->lastModified)->filter()->max();
                }

                $this->atomicPut($disk, "{$directory}/index.xml", $this->indexXml($index));
                $this->atomicPut($disk, "{$directory}/generated.json", (string) json_encode([
                    'generated_at' => now('UTC')->toAtomString(),
                    'host' => $this->settings->canonicalBaseUrl(),
                    'counts' => $counts,
                ], JSON_THROW_ON_ERROR));

                return $counts;
            });
        } catch (LockTimeoutException) {
            // Another worker is already regenerating; its output is equivalent.
            return [];
        }
    }

    /** Stored file contents, or null when it has not been generated. */
    public function stored(string $name): ?string
    {
        $disk = Storage::disk((string) config('impact.seo.sitemap_disk', 'local'));
        $path = (string) config('impact.seo.sitemap_directory', 'seo/sitemaps')."/{$name}.xml";
        if (! $disk->exists($path)) {
            return null;
        }

        // A file generated for another host (for example before the canonical
        // URL was configured) is never served.
        $xml = (string) $disk->get($path);

        return str_contains($xml, '<loc>'.$this->xml($this->settings->canonicalBaseUrl())) ? $xml : null;
    }

    /** @return array{generated_at: string, host: string, counts: array<string, int>}|null */
    public function status(): ?array
    {
        $disk = Storage::disk((string) config('impact.seo.sitemap_disk', 'local'));
        $path = (string) config('impact.seo.sitemap_directory', 'seo/sitemaps').'/generated.json';
        $decoded = $disk->exists($path) ? json_decode((string) $disk->get($path), true) : null;

        return is_array($decoded) ? $decoded : null;
    }

    /** @return Collection<int, SitemapEntry> */
    private function pageEntries(): Collection
    {
        $overrides = SeoOverride::query()->where('subject_type', 'page')->get()->keyBy('subject_key');

        return collect(self::PAGES)
            ->reject(fn (string $key, string $route): bool => isset(self::FEATURE_PAGES[$route]) && ! $this->effective->boolean(self::FEATURE_PAGES[$route]))
            ->reject(fn (string $key): bool => $this->excluded($overrides->get($key)))
            ->map(function (string $key, string $route): SitemapEntry {
                $type = collect(PublicResourceType::cases())->first(static fn (PublicResourceType $type): bool => $type->indexRoute() === $route);
                $lastModified = $type === null ? null : $this->resources->current($type)
                    ->map(fn (Model $record): ?CarbonInterface => $this->lastModified($record))
                    ->filter()
                    ->max();

                return new SitemapEntry($this->urls->forRoute($route), $lastModified);
            })
            ->values();
    }

    /** @return Collection<int, SitemapEntry> */
    private function resourceEntries(PublicResourceType $type): Collection
    {
        $overrides = SeoOverride::query()->where('subject_type', $type->value)->get()->keyBy('subject_key');

        return $this->resources->current($type)
            ->reject(fn (Model $record): bool => $record instanceof Vacancy && ! $record->acceptsApplications())
            ->reject(fn (Model $record): bool => $this->excluded($overrides->get((string) $record->getAttribute($type->parentKey()))))
            ->map(fn (Model $record): SitemapEntry => new SitemapEntry(
                $this->publicUrls->url($type, (string) $record->getAttribute('slug')),
                $this->lastModified($record),
            ))
            ->values();
    }

    /** Excluded by an editor: noindex, sitemap opt-out or a canonical elsewhere. */
    private function excluded(?SeoOverride $override): bool
    {
        if ($override === null) {
            return false;
        }

        return ! $override->include_in_sitemap
            || (RobotsDirective::parse($override->robots)?->isIndexable() === false)
            || filled($override->canonical_path);
    }

    private function lastModified(Model $record): ?CarbonInterface
    {
        $value = $record instanceof Event ? $record->updated_at : ($record->getAttribute('updated_at') ?? $record->getAttribute('created_at'));

        return $value === null ? null : CarbonImmutable::parse($value)->utc();
    }

    private function atomicPut(Filesystem $disk, string $path, string $contents): void
    {
        // Write beside the target, then rename over it: readers see either
        // the previous file or the complete new one, never a partial write.
        $temporary = $path.'.'.Str::random(8).'.tmp';
        $disk->put($temporary, $contents);
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $disk->move($temporary, $path);

                return;
            } catch (UnableToMoveFile $exception) {
                // Windows refuses to replace a file another process is reading.
                if ($attempt === 3) {
                    $disk->delete($temporary);

                    throw $exception;
                }
                usleep(50_000 * $attempt);
            }
        }
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
