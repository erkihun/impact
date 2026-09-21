<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Search\RecordSearchQueryAction;
use App\Data\Search\RecordSearchQueryData;
use App\Http\Controllers\Controller;
use App\Models\SearchDocument;
use App\Support\Settings\SearchSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    public function __invoke(
        Request $request,
        RecordSearchQueryAction $recordSearch,
        SearchSettings $settings,
    ): View {
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

        return view('public.search', [
            'query' => $query,
            'results' => $results,
            'usesV2Presentation' => $settings->usesV2Presentation(),
        ]);
    }
}
