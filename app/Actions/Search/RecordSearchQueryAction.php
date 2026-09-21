<?php

declare(strict_types=1);

namespace App\Actions\Search;

use App\Data\Search\RecordSearchQueryData;
use App\Models\SearchQueryLog;
use App\Support\Settings\SearchSettings;

final readonly class RecordSearchQueryAction
{
    public function __construct(private SearchSettings $settings) {}

    public function execute(RecordSearchQueryData $data): void
    {
        if ($data->query === '' || ! $this->settings->logsQueries()) {
            return;
        }

        SearchQueryLog::query()->create([
            'locale' => $data->locale,
            'query_hash' => hash_hmac('sha256', mb_strtolower($data->query), (string) config('app.key')),
            'result_count' => $data->resultCount,
        ]);
    }
}
