<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Contracts\SearchIndexer;
use App\Models\SearchDocument;

final readonly class DatabaseSearchIndexer implements SearchIndexer
{
    public function upsert(string $type, string $id, string $locale, array $document): void
    {
        SearchDocument::query()->updateOrCreate(
            ['searchable_type' => $type, 'searchable_id' => $id, 'locale' => $locale],
            [
                'title' => $document['title'],
                'summary' => $document['summary'] ?? null,
                'body' => $document['body'],
                'url' => $document['url'],
                'filters' => $document['filters'] ?? [],
                'published_at' => $document['published_at'] ?? now('UTC'),
            ],
        );
    }

    public function delete(string $type, string $id, string $locale): void
    {
        SearchDocument::query()
            ->where('searchable_type', $type)
            ->where('searchable_id', $id)
            ->where('locale', $locale)
            ->delete();
    }
}
