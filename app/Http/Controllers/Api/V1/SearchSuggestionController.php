<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SearchDocument;
use App\Support\Settings\SearchSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request, SearchSettings $settings): JsonResponse
    {
        abort_unless($settings->enabled(), 404);
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'locale' => ['nullable', 'in:en,am'],
        ]);

        $suggestions = SearchDocument::query()
            ->where('locale', $validated['locale'] ?? app()->getLocale())
            ->where('title', 'like', $validated['q'].'%')
            ->orderBy('title')
            ->limit($settings->suggestionLimit())
            ->get(['title', 'url', 'searchable_type']);

        return response()->json(['data' => $suggestions]);
    }
}
