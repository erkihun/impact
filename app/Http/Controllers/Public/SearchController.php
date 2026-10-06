<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Search\RecordSearchQueryAction;
use App\Data\Search\RecordSearchQueryData;
use App\Enums\Seo\RobotsDirective;
use App\Http\Controllers\Controller;
use App\Models\SearchDocument;
use App\Services\Seo\SeoMetadataBuilder;
use App\Services\Seo\SeoSettings;
use App\Support\Inertia\PublicPage;
use App\Support\Settings\SearchSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Response;

final class SearchController extends Controller
{
    public function __invoke(
        Request $request,
        RecordSearchQueryAction $recordSearch,
        SearchSettings $settings,
        SeoSettings $seoSettings,
    ): Response {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', 'string', 'max:80'],
        ]);
        $query = trim((string) ($validated['q'] ?? ''));

        $results = SearchDocument::query()
            ->where('locale', app()->getLocale())
            ->when($query !== '', fn ($builder) => $builder->where(
                fn ($nested) => $nested->where('title', 'like', "%{$query}%")
                    ->orWhere('summary', 'like', "%{$query}%"),
            ))
            ->when(filled($validated['type'] ?? null), fn ($builder) => $builder
                ->where('searchable_type', $validated['type']))
            ->when($query !== '', fn ($builder) => $builder->orderByRaw(
                'CASE WHEN LOWER(title) = ? THEN 0 WHEN LOWER(title) LIKE ? THEN 1 ELSE 2 END',
                [mb_strtolower($query), mb_strtolower($query).'%'],
            ))
            ->latest('published_at')
            ->paginate($settings->resultsPerPage())
            ->withQueryString();
        $recordSearch->execute(new RecordSearchQueryData(
            locale: app()->getLocale(),
            query: $query,
            resultCount: $results->total(),
        ));

        // Internal search helps visitors but is never an indexable landing
        // page: noindex, follow, a query-free canonical and no sitemap entry.
        $seo = SeoMetadataBuilder::make()
            ->subject('page', 'search')
            ->title(__('Search'))
            ->description(__('Search published services, sector experience, experts, case studies, insights, events and opportunities from Impact Consulting.'))
            ->type('website', 'SearchResultsPage')
            ->meaningfulQuery([])
            ->robots($seoSettings->searchResultsNoindex() ? RobotsDirective::NoindexFollow : RobotsDirective::IndexFollow);

        return PublicPage::render('Public/Search', 'search', [
            'breadcrumbs' => PublicPage::breadcrumbs([__('Search') => null]),
            'header' => [
                'eyebrow' => __('Knowledge discovery'),
                'title' => __('Search'),
                'summary' => __('Find published services, sector experience, experts, evidence, insights, events and opportunities.'),
            ],
            'query' => $query,
            'action' => route('search'),
            'total' => $results->total(),
            'results' => collect($results->items())->values()->map(fn (SearchDocument $result, int $index): array => [
                'number' => str_pad((string) ($results->firstItem() + $index), 2, '0', STR_PAD_LEFT),
                'type' => Str::headline(str_replace('_', ' ', (string) $result->searchable_type)),
                'title' => $result->title,
                'summary' => $result->summary,
                'href' => $result->url,
            ])->all(),
            'pagination' => [
                'previous' => $results->onFirstPage() ? null : $results->previousPageUrl(),
                'next' => $results->hasMorePages() ? $results->nextPageUrl() : null,
            ],
            'copy' => [
                'label' => __('Search the website'),
                'placeholder' => __('Search insights, services and more'),
                'submit' => __('Search'),
                'resultsHeading' => __('Search results'),
                'count' => trans_choice(':count result|:count results', $results->total(), ['count' => $results->total()]),
                'resultsFor' => filled($query) ? __('Results for “:query”', ['query' => $query]) : null,
                'emptyTitle' => filled($query) ? __('No results matched “:query”.', ['query' => $query]) : __('Start with a topic, service or name.'),
                'emptyDescription' => __('Try fewer words or use one of the trusted routes below to continue.'),
                'clear' => __('Clear search'),
                'exploreServices' => __('Explore services'),
                'browseInsights' => __('Browse insights'),
                'previous' => __('Previous'),
                'next' => __('Next'),
                'pagination' => __('Pagination'),
            ],
            'links' => [
                'services' => route('services.index'),
                'insights' => route('insights.index'),
            ],
        ], seo: $seo);
    }
}
