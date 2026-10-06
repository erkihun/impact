<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Data\Seo\SeoIssue;
use App\Data\Seo\SeoValidationResult;
use App\Enums\Seo\PublicResourceType;
use App\Enums\Seo\RobotsDirective;
use App\Enums\Seo\SeoIssueSeverity;
use App\Models\MediaAsset;
use App\Models\SeoAuditRun;
use App\Models\SeoLinkCheck;
use App\Queries\Seo\PublicResourceQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/**
 * Site-wide SEO audit. Every indexable URL is rendered through the HTTP
 * kernel and checked as a crawler would see it; site-level checks cover the
 * English-only URL structure, private routes, redirects, links, orphans and
 * images. Findings are Blocking / Warning / Information, never a score.
 */
final readonly class SeoAuditService
{
    public function __construct(
        private SitemapBuilder $sitemaps,
        private PageInspector $inspector,
        private StructuredDataValidator $structuredData,
        private RedirectAuditor $redirects,
        private InternalLinkService $links,
        private PublicResourceQuery $resources,
        private SeoSettings $settings,
        private CanonicalUrlBuilder $urls,
    ) {}

    /**
     * @return array{result: SeoValidationResult, metrics: array<string, mixed>, pages: list<array<string, mixed>>}
     */
    public function run(bool $persist = false, string $trigger = 'command'): array
    {
        $result = new SeoValidationResult;
        $pages = [];
        $titles = [];
        $descriptions = [];
        $missingTitles = 0;
        $missingDescriptions = 0;
        $missingImages = 0;
        $structuredIssues = 0;

        $entries = collect($this->sitemaps->all())->flatten(1);
        $fallbackDescription = SeoMetadataBuilder::make()->normalizeDescription($this->settings->defaultDescription());

        foreach ($entries as $entry) {
            $path = $this->urls->normalizePath((string) parse_url($entry->loc, PHP_URL_PATH));
            $page = $this->inspector->inspect($path);
            $seo = $page['seo'];
            $url = $this->urls->url($path);
            $pages[] = [
                'path' => $path,
                'status' => $page['status'],
                'title' => $seo['title'] ?? null,
                'description' => $seo['description'] ?? null,
                'canonical' => $seo['canonical'] ?? null,
                'robots' => $seo['intendedRobots'] ?? null,
                'h1' => $page['h1'],
                'structuredData' => $seo['structuredDataTypes'] ?? [],
                'image' => $seo['image']['url'] ?? null,
            ];

            if ($page['status'] !== 200) {
                $result->add(new SeoIssue('sitemap-url-not-ok', SeoIssueSeverity::Blocking, "Sitemap URL returns HTTP {$page['status']}.", $path));

                continue;
            }

            $title = trim((string) ($seo['title'] ?? ''));
            if ($title === '') {
                $missingTitles++;
                $result->add(new SeoIssue('title-missing', SeoIssueSeverity::Blocking, 'Page has no title.', $path));
            } else {
                $titles[mb_strtolower($title)][] = $path;
                if (mb_strlen($title) > ContentSeoValidator::TITLE_MAX) {
                    $result->add(new SeoIssue('title-too-long', SeoIssueSeverity::Warning, 'Title is '.mb_strlen($title).' characters.', $path));
                } elseif (mb_strlen($title) < ContentSeoValidator::TITLE_MIN) {
                    $result->add(new SeoIssue('title-too-short', SeoIssueSeverity::Warning, 'Title is only '.mb_strlen($title).' characters.', $path));
                }
            }

            $description = trim((string) ($seo['description'] ?? ''));
            if ($description === '' || $description === $fallbackDescription) {
                $missingDescriptions++;
                $result->add(new SeoIssue('description-missing', SeoIssueSeverity::Warning, 'Page uses the site-wide fallback description.', $path));
            } else {
                $descriptions[mb_strtolower($description)][] = $path;
                if (mb_strlen($description) < ContentSeoValidator::DESCRIPTION_MIN) {
                    $result->add(new SeoIssue('description-too-short', SeoIssueSeverity::Warning, 'Description is only '.mb_strlen($description).' characters.', $path));
                }
            }

            $canonical = $seo['canonical'] ?? null;
            if ($canonical !== $url) {
                $result->add(new SeoIssue('canonical-mismatch', SeoIssueSeverity::Blocking, 'Sitemap URL is not its own canonical ('.($canonical ?? 'none').').', $path));
            }
            if (app()->isProduction() && ! str_starts_with((string) $canonical, 'https://')) {
                $result->add(new SeoIssue('canonical-not-https', SeoIssueSeverity::Blocking, 'Canonical URL is not HTTPS.', $path));
            }
            if (RobotsDirective::parse($seo['intendedRobots'] ?? null) !== RobotsDirective::IndexFollow) {
                $result->add(new SeoIssue('sitemap-noindex', SeoIssueSeverity::Blocking, 'Sitemap lists a page that is not indexable.', $path));
            }

            $h1 = array_values(array_filter($page['h1'], static fn (string $heading): bool => $heading !== ''));
            if ($h1 === []) {
                $result->add(new SeoIssue('h1-missing', SeoIssueSeverity::Blocking, 'Page has no main heading.', $path));
            } elseif (count($page['h1']) > 1) {
                $result->add(new SeoIssue('h1-multiple', SeoIssueSeverity::Warning, count($page['h1']).' main headings; use one <h1>.', $path));
            }

            if (empty($seo['image']['url'])) {
                $missingImages++;
                $result->add(new SeoIssue('social-image-missing', SeoIssueSeverity::Warning, 'No social sharing image.', $path));
            } elseif (empty($seo['image']['alt'])) {
                $result->add(new SeoIssue('social-image-alt-missing', SeoIssueSeverity::Warning, 'Social image has no alternative text.', $path));
            }

            if ($page['jsonLd'] === []) {
                $result->add(new SeoIssue('structured-data-missing', SeoIssueSeverity::Warning, 'Page has no structured data.', $path));
            }
            $validation = $this->structuredData->validateScripts($page['jsonLd'], $path);
            $structuredIssues += count($validation->issues());
            $result->merge($validation);

            foreach ($page['head'] as $element) {
                if (str_contains($element, 'hreflang') || str_contains($element, 'x-default')) {
                    $result->add(new SeoIssue('stale-language-alternate', SeoIssueSeverity::Blocking, 'A language-alternate tag is still emitted on an English-only site.', $path));
                }
                if (str_contains($element, 'name="keywords"')) {
                    $result->add(new SeoIssue('meta-keywords', SeoIssueSeverity::Warning, 'meta keywords is obsolete and must not be emitted.', $path));
                }
            }
        }

        foreach ($titles as $paths) {
            if (count($paths) > 1) {
                foreach ($paths as $path) {
                    $result->add(new SeoIssue('title-duplicate', SeoIssueSeverity::Warning, 'Title is shared with '.(count($paths) - 1).' other page(s).', $path));
                }
            }
        }
        foreach ($descriptions as $paths) {
            if (count($paths) > 1) {
                foreach ($paths as $path) {
                    $result->add(new SeoIssue('description-duplicate', SeoIssueSeverity::Warning, 'Description is shared with '.(count($paths) - 1).' other page(s).', $path));
                }
            }
        }

        $result->merge($this->siteChecks($entries->pluck('loc')->all()));
        $redirectResult = $this->redirects->validate();
        $result->merge($redirectResult);
        $orphans = $this->orphanChecks();
        $result->merge($orphans);
        $brokenLinks = $this->linkChecks();
        $result->merge($brokenLinks);
        $images = $this->imageChecks();
        $result->merge($images);

        $metrics = [
            'indexable_pages' => $entries->count(),
            'blocking' => $result->count(SeoIssueSeverity::Blocking),
            'warnings' => $result->count(SeoIssueSeverity::Warning),
            'information' => $result->count(SeoIssueSeverity::Information),
            'missing_titles' => $missingTitles,
            'missing_descriptions' => $missingDescriptions,
            'missing_social_images' => $missingImages,
            'duplicate_titles' => $result->countCode('title-duplicate'),
            'duplicate_descriptions' => $result->countCode('description-duplicate'),
            'broken_links' => count($brokenLinks->issues()),
            'redirect_problems' => $redirectResult->count(SeoIssueSeverity::Blocking) + $redirectResult->count(SeoIssueSeverity::Warning),
            'structured_data_issues' => $structuredIssues,
            'orphan_pages' => count($orphans->issues()),
            'oversized_images' => $images->countCode('image-oversized') + $images->countCode('static-image-oversized'),
            'sitemap' => $this->sitemaps->status(),
            'indexing_allowed' => $this->settings->indexingAllowed(),
            'canonical_base_url' => $this->settings->canonicalBaseUrl(),
        ];

        if ($persist) {
            SeoAuditRun::query()->create([
                'blocking_count' => $metrics['blocking'],
                'warning_count' => $metrics['warnings'],
                'information_count' => $metrics['information'],
                'metrics' => [...$metrics, 'pages' => array_slice($pages, 0, 500)],
                'issues' => array_slice($result->toArray()['issues'], 0, 500),
                'trigger' => $trigger,
            ]);
        }

        return ['result' => $result, 'metrics' => $metrics, 'pages' => $pages];
    }

    /** @param  list<string>  $sitemapUrls */
    private function siteChecks(array $sitemapUrls): SeoValidationResult
    {
        $result = new SeoValidationResult;

        if (app()->isProduction() && ! str_starts_with($this->settings->canonicalBaseUrl(), 'https://')) {
            $result->add(new SeoIssue('canonical-host-not-https', SeoIssueSeverity::Blocking, 'Configure SEO_CANONICAL_URL (or APP_URL) with https:// for production.'));
        }

        $html = $this->inspector->html('/');
        if (! str_contains($html, '<html lang="en"')) {
            $result->add(new SeoIssue('html-lang', SeoIssueSeverity::Blocking, 'The homepage does not declare <html lang="en">.', '/'));
        }
        if (! str_contains($html, '<title data-inertia="title">') || ! str_contains($html, 'rel="canonical"')) {
            $result->add(new SeoIssue('head-not-server-rendered', SeoIssueSeverity::Blocking, 'Title and canonical are not in the server-rendered HTML.', '/'));
        }
        if (str_contains($html, 'hreflang')) {
            $result->add(new SeoIssue('stale-language-alternate', SeoIssueSeverity::Blocking, 'The homepage HTML still contains hreflang.', '/'));
        }

        foreach (['/en' => '/', '/en/services' => '/services', '/en/about' => '/about'] as $legacy => $expected) {
            $page = $this->inspector->inspect($legacy);
            if ($page['status'] !== 301 || $this->urls->normalizePath((string) parse_url((string) $page['location'], PHP_URL_PATH)) !== $expected) {
                $result->add(new SeoIssue('legacy-english-duplicate', SeoIssueSeverity::Blocking, "{$legacy} must permanently redirect to {$expected}.", $legacy));
            }
        }
        foreach (['/am', '/am/services', '/am/unknown-retired-page'] as $legacy) {
            $page = $this->inspector->inspect($legacy);
            if (! in_array($page['status'], [301, 410], true)) {
                $result->add(new SeoIssue('legacy-amharic-url', SeoIssueSeverity::Blocking, "{$legacy} returns HTTP {$page['status']} instead of 301 or 410.", $legacy));
            }
            if ($page['status'] === 301 && $this->urls->normalizePath((string) parse_url((string) $page['location'], PHP_URL_PATH)) === '/' && $legacy !== '/am') {
                $result->add(new SeoIssue('legacy-amharic-homepage-redirect', SeoIssueSeverity::Warning, "{$legacy} is redirected to the homepage instead of an equivalent page.", $legacy));
            }
        }

        foreach ($sitemapUrls as $loc) {
            $path = (string) parse_url($loc, PHP_URL_PATH);
            if (preg_match('#^/(en|am)(/|$)#', $path) === 1) {
                $result->add(new SeoIssue('sitemap-legacy-locale', SeoIssueSeverity::Blocking, 'Sitemap lists a retired language URL.', $path));
            }
            if (preg_match('#^/(admin|dashboard|profile|login|mfa|search|restricted-media|application-files|submission-files)(/|$)#', $path) === 1 || str_contains($loc, '?')) {
                $result->add(new SeoIssue('sitemap-private-or-transient', SeoIssueSeverity::Blocking, 'Sitemap lists a private, search or query URL.', $path));
            }
        }

        foreach (['/admin', '/login', '/dashboard'] as $private) {
            $page = $this->inspector->inspect($private);
            $metaNoindex = collect($page['head'])->contains(static fn (string $element): bool => str_contains($element, 'name="robots"') && str_contains($element, 'noindex'));
            if (! str_contains((string) $page['robotsHeader'], 'noindex') && ! $metaNoindex) {
                $result->add(new SeoIssue('private-route-indexable', SeoIssueSeverity::Blocking, 'Private route is not marked noindex.', $private));
            }
        }

        $search = $this->inspector->inspect('/search');
        if ($search['status'] === 200 && RobotsDirective::parse($search['seo']['intendedRobots'] ?? null)?->isIndexable() && $this->settings->searchResultsNoindex()) {
            $result->add(new SeoIssue('search-indexable', SeoIssueSeverity::Blocking, 'Internal search results must be noindex.', '/search'));
        }

        $robots = $this->inspector->html('/robots.txt');
        if ($this->settings->indexingAllowed()) {
            if (str_contains($robots, "Disallow: /\n")) {
                $result->add(new SeoIssue('robots-blocks-site', SeoIssueSeverity::Blocking, 'robots.txt disallows the whole production site.', '/robots.txt'));
            }
            if ($this->settings->sitemapEnabled() && ! str_contains($robots, 'Sitemap: '.$this->urls->forRoute('sitemap.index'))) {
                $result->add(new SeoIssue('robots-sitemap-missing', SeoIssueSeverity::Warning, 'robots.txt does not reference the sitemap.', '/robots.txt'));
            }
        } else {
            if (! str_contains($robots, "Disallow: /\n")) {
                $result->add(new SeoIssue('robots-nonproduction-open', SeoIssueSeverity::Blocking, 'Non-production robots.txt must disallow all crawling.', '/robots.txt'));
            }
            $result->add(new SeoIssue('indexing-disabled', SeoIssueSeverity::Information, 'Indexing is disabled in this environment; all pages send noindex, nofollow. Results describe production intent.'));
        }
        if (is_file(public_path('robots.txt'))) {
            $result->add(new SeoIssue('static-robots-file', SeoIssueSeverity::Blocking, 'public/robots.txt shadows the environment-aware robots route; delete it.'));
        }

        if (! $this->settings->sitemapEnabled()) {
            $result->add(new SeoIssue('sitemap-disabled', SeoIssueSeverity::Warning, 'The XML sitemap is disabled.'));
        } elseif ($this->sitemaps->status() === null) {
            $result->add(new SeoIssue('sitemap-not-generated', SeoIssueSeverity::Information, 'Sitemap files have not been generated; they are built on request. Run seo:sitemap-generate.'));
        }

        return $result;
    }

    private function orphanChecks(): SeoValidationResult
    {
        $result = new SeoValidationResult;
        foreach ([PublicResourceType::Service, PublicResourceType::Industry, PublicResourceType::Expert, PublicResourceType::CaseStudy, PublicResourceType::Insight] as $type) {
            $this->resources->current($type)->each(function (Model $record) use ($type, $result): void {
                if ($this->links->contextualLinkCount($type, $this->links->identity($type, $record)) === 0) {
                    $result->add(new SeoIssue('orphan-page', SeoIssueSeverity::Warning, 'Only reachable from listings; no related content links to it.', route($type->showRoute(), ['slug' => $record->getAttribute('slug')], false), $type->label()));
                }
            });
        }

        return $result;
    }

    private function linkChecks(): SeoValidationResult
    {
        $result = new SeoValidationResult;
        SeoLinkCheck::query()->where('resolution_status', 'open')->whereIn('result', ['broken', 'error'])->orderBy('url')->limit(200)->get()
            ->each(fn (SeoLinkCheck $link) => $result->add(new SeoIssue(
                'broken-link',
                $link->severity === 'blocking' ? SeoIssueSeverity::Blocking : SeoIssueSeverity::Warning,
                "Broken link to {$link->url} (".($link->status_code ?? 'no response').") from {$link->source_label}.",
                (string) parse_url($link->source_url, PHP_URL_PATH) ?: '/',
            )));

        return $result;
    }

    private function imageChecks(): SeoValidationResult
    {
        $result = new SeoValidationResult;
        $hardLimit = (int) config('impact.seo.image_budgets_kb.hero_hard_limit', 1024) * 1024;
        $budget = (int) config('impact.seo.image_budgets_kb.hero', 500) * 1024;

        MediaAsset::query()
            ->with('variants')
            ->where('visibility', 'public')
            ->where('mime_type', 'like', 'image/%')
            ->where('scan_status', 'clean')
            ->where('processing_status', 'ready')
            ->get()
            ->each(function (MediaAsset $asset) use ($result, $hardLimit): void {
                $label = $asset->title ?: $asset->original_name;
                if (blank($asset->alt_text)) {
                    $result->add(new SeoIssue('image-alt-missing', SeoIssueSeverity::Warning, 'Public image has no alternative text; add one or mark each usage decorative.', null, $label));
                }
                if ($asset->variants->isEmpty() && $asset->mime_type !== 'image/svg+xml') {
                    $result->add(new SeoIssue('image-dimensions-missing', SeoIssueSeverity::Warning, 'Public image has no optimized derivatives, so no dimensions or srcset.', null, $label));
                    if ($asset->size_bytes > $hardLimit) {
                        $result->add(new SeoIssue('image-oversized', SeoIssueSeverity::Warning, 'Original over 1 MB is delivered without an optimized derivative.', null, $label));
                    }
                }
            });

        foreach (File::allFiles(public_path('images')) as $file) {
            if (! preg_match('/\.(jpe?g|png|webp|avif|gif)$/i', $file->getFilename())) {
                continue;
            }
            $relative = 'images/'.str_replace('\\', '/', $file->getRelativePathname());
            if ($file->getSize() > $hardLimit) {
                $result->add(new SeoIssue('static-image-oversized', SeoIssueSeverity::Warning, 'Public image is over 1 MB; generate derivatives with seo:images-optimize.', '/'.$relative));
            } elseif ($file->getSize() > $budget) {
                $result->add(new SeoIssue('static-image-over-budget', SeoIssueSeverity::Information, 'Public image is over the 500 KB hero budget; it is offered only to large, high-density screens.', '/'.$relative));
            }
        }

        return $result;
    }
}
