<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Enums\Seo\PublicResourceType;
use App\Enums\Seo\RedirectOrigin;
use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Models\SeoAuditRun;
use App\Models\SeoLinkCheck;
use App\Models\SeoMetadata;
use App\Queries\Seo\PublicResourceQuery;
use App\Services\Seo\LinkChecker;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Seo\RedirectAuditor;
use App\Services\Seo\SeoAuditService;
use App\Services\Seo\SeoSettings;
use App\Services\Seo\SitemapBuilder;
use App\Support\CorrelationContext;
use App\Support\Inertia\WorkspacePage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Admin SEO centre: overview, pages, metadata, sitemap, redirects,
 * structured data, broken links, indexing, content quality, social
 * preview, settings and audit history. All figures come from this site's
 * own audit; no external ranking data is shown.
 */
final class SeoCenterController extends Controller
{
    public const SECTIONS = [
        'overview' => 'Overview',
        'pages' => 'Pages',
        'metadata' => 'Metadata',
        'sitemap' => 'Sitemap',
        'redirects' => 'Redirects',
        'structured-data' => 'Structured data',
        'links' => 'Broken links',
        'indexing' => 'Indexing',
        'quality' => 'Content quality',
        'social' => 'Social preview',
        'settings' => 'SEO settings',
        'audit' => 'Audit',
    ];

    public function index(
        Request $request,
        SeoSettings $settings,
        SitemapBuilder $sitemaps,
        PublicResourceQuery $resources,
        PublicUrlGenerator $urls,
        RedirectAuditor $redirectAuditor,
    ): Response {
        $section = array_key_exists((string) $request->query('section'), self::SECTIONS) ? (string) $request->query('section') : 'overview';
        $latest = SeoAuditRun::query()->latest('created_at')->first();
        $issues = collect($latest->issues ?? []);
        $issuesFor = static fn (string ...$prefixes) => $issues
            ->filter(static fn (array $issue): bool => collect($prefixes)->contains(static fn (string $prefix): bool => str_starts_with((string) $issue['code'], $prefix)))
            ->values()
            ->all();

        $data = match ($section) {
            'overview' => [
                'metrics' => $latest?->metrics,
                'openBrokenLinks' => SeoLinkCheck::query()->where('resolution_status', 'open')->count(),
                'redirects' => Redirect::query()->where('enabled', true)->count(),
                'legacyDecisions' => Redirect::query()->where('origin', RedirectOrigin::LegacyLocale->value)->count(),
                'blocking' => $issuesFor(''),
            ],
            'pages', 'social' => ['pages' => $this->pages($resources, $urls)],
            'metadata' => ['pages' => $latest?->metrics['pages'] ?? [], 'issues' => $issuesFor('title-', 'description-')],
            'sitemap' => [
                'status' => $sitemaps->status(),
                'segments' => collect($sitemaps->segments())->map(fn (string $segment): array => [
                    'segment' => $segment,
                    'url' => route('sitemap.segment', ['segment' => $segment]),
                    'count' => $sitemaps->entries($segment)->count(),
                ])->all(),
                'index' => route('sitemap.index'),
                'enabled' => $settings->sitemapEnabled(),
                'frequency' => $settings->sitemapFrequency(),
            ],
            'redirects' => [
                'redirects' => Redirect::query()
                    ->when($request->filled('origin'), fn ($query) => $query->where('origin', $request->string('origin')))
                    ->when($request->filled('q'), fn ($query) => $query->where('source_path', 'like', '%'.$request->string('q').'%'))
                    ->orderByDesc('updated_at')
                    ->paginate(50)
                    ->withQueryString(),
                'origins' => collect(RedirectOrigin::cases())->mapWithKeys(static fn (RedirectOrigin $origin): array => [$origin->value => $origin->label()])->all(),
                'validation' => $redirectAuditor->validate()->toArray(),
            ],
            'structured-data' => ['issues' => $issuesFor('structured-data'), 'pages' => $latest?->metrics['pages'] ?? []],
            'links' => [
                'links' => SeoLinkCheck::query()
                    ->whereIn('result', ['broken', 'error', 'redirect'])
                    ->orderByRaw("CASE resolution_status WHEN 'open' THEN 0 ELSE 1 END")
                    ->orderBy('severity')
                    ->paginate(50)
                    ->withQueryString(),
                'lastChecked' => SeoLinkCheck::query()->max('last_checked_at'),
            ],
            'indexing' => [
                'environment' => app()->environment(),
                'indexingAllowed' => $settings->indexingAllowed(),
                'canonicalBaseUrl' => $settings->canonicalBaseUrl(),
                'robots' => $settings->indexingAllowed() ? null : "User-agent: *\nDisallow: /",
                'robotsUrl' => route('robots'),
                'searchNoindex' => $settings->searchResultsNoindex(),
                'archivedGone' => $settings->archivedContentIsGone(),
                'googleVerification' => $settings->googleVerification() !== null,
                'bingVerification' => $settings->bingVerification() !== null,
                'issues' => $issuesFor('private-', 'search-', 'robots-', 'legacy-', 'sitemap-', 'canonical-', 'html-lang', 'stale-language', 'indexing-'),
            ],
            'quality' => ['issues' => $issuesFor('h1-', 'orphan-', 'image-', 'static-image', 'social-image', 'broken-link')],
            'settings' => [
                'values' => [
                    'Homepage title' => $settings->homeTitle(),
                    'Title suffix' => $settings->titleSuffix(),
                    'Fallback description' => $settings->defaultDescription(),
                    'Canonical site URL' => $settings->canonicalBaseUrl(),
                    'Twitter/X card' => $settings->twitterCard(),
                    'Open Graph locale' => $settings->ogLocale(),
                    'Organization type' => $settings->organizationType(),
                    'Social profiles' => implode(', ', $settings->socialProfiles()) ?: '—',
                    'Previews' => 'Always noindex, nofollow (not configurable)',
                ],
                'editUrl' => route('admin.settings.show', ['category' => 'seo']),
            ],
            'audit' => [
                'runs' => SeoAuditRun::query()->latest('created_at')->limit(15)->get(['id', 'blocking_count', 'warning_count', 'information_count', 'trigger', 'created_at']),
                'issues' => $issues->all(),
            ],
        };

        return WorkspacePage::render('admin.seo.index', [
            'section' => $section,
            'sections' => self::SECTIONS,
            'latestAudit' => $latest === null ? null : [
                'created_at' => $latest->created_at?->toAtomString(),
                'blocking' => $latest->blocking_count,
                'warnings' => $latest->warning_count,
                'information' => $latest->information_count,
                'status' => $latest->blocking_count > 0 ? 'SEO blocking issues' : ($latest->warning_count > 0 ? 'SEO warnings' : 'SEO ready'),
            ],
            'data' => $data,
        ]);
    }

    public function audit(SeoAuditService $audit, AuditRecorder $recorder, CorrelationContext $correlation, Request $request): RedirectResponse
    {
        $report = $audit->run(persist: true, trigger: 'admin');
        $recorder->record(new AuditData(
            action: 'seo.audit.run',
            auditableType: SeoAuditRun::class,
            auditableId: null,
            actorId: (string) $request->user()?->getKey(),
            correlationId: $correlation->id(),
            metadata: ['blocking' => $report['metrics']['blocking'], 'warnings' => $report['metrics']['warnings']],
        ));

        return back()->with('status', __('SEO audit completed: :status.', ['status' => __($report['result']->statusLabel())]));
    }

    public function sitemap(SitemapBuilder $sitemaps): RedirectResponse
    {
        $counts = $sitemaps->generate();

        return back()->with('status', __('Sitemap regenerated with :count URLs.', ['count' => array_sum($counts)]));
    }

    public function links(LinkChecker $checker): RedirectResponse
    {
        $summary = $checker->run(false);

        return back()->with('status', __(':checked links checked; :broken broken.', $summary));
    }

    public function resolveLink(Request $request, SeoLinkCheck $link): RedirectResponse
    {
        $validated = $request->validate(['resolution_status' => ['required', 'in:open,resolved,ignored']]);
        $link->forceFill(['resolution_status' => $validated['resolution_status']])->save();

        return back()->with('status', __('Link status updated.'));
    }

    /** @return list<array<string, mixed>> */
    private function pages(PublicResourceQuery $resources, PublicUrlGenerator $urls): array
    {
        $overrides = SeoMetadata::query()->get()->keyBy(static fn (SeoMetadata $meta): string => $meta->subject_type.'|'.$meta->subject_key);
        $pages = collect(SitemapBuilder::pageKeys())->map(static fn (string $key, string $route): array => [
            'type' => 'page',
            'typeLabel' => 'Page',
            'key' => $key,
            'name' => str($key)->replace(['.index', 'legal.', '.'], ['', '', ' '])->headline()->toString(),
            'path' => route($route, [], false),
            'override' => $overrides->has('page|'.$key),
            'robots' => $overrides->get('page|'.$key)->robots ?? 'index, follow',
            'editUrl' => route('admin.seo.pages.edit', ['type' => 'page', 'key' => $key]),
        ])->values();

        foreach (PublicResourceType::cases() as $type) {
            $resources->current($type)->each(function (Model $record) use ($type, $urls, $overrides, $pages): void {
                $identity = (string) $record->getAttribute($type->parentKey());
                $pages->push([
                    'type' => $type->value,
                    'typeLabel' => $type->label(),
                    'key' => $identity,
                    'name' => (string) $record->getAttribute($type->titleField()),
                    'path' => $urls->path($type, (string) $record->getAttribute('slug')),
                    'override' => $overrides->has($type->value.'|'.$identity),
                    'robots' => $overrides->get($type->value.'|'.$identity)->robots ?? 'index, follow',
                    'editUrl' => route('admin.seo.pages.edit', ['type' => $type->value, 'key' => $identity]),
                ]);
            });
        }

        return $pages->all();
    }
}
