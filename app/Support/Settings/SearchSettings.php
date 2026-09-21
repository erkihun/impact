<?php

declare(strict_types=1);

namespace App\Support\Settings;

final readonly class SearchSettings
{
    public function __construct(private EffectiveSettings $settings) {}

    public function enabled(): bool
    {
        return $this->settings->boolean('search.enabled');
    }

    public function resultsPerPage(): int
    {
        return min(
            $this->settings->integer('search.results_per_page'),
            $this->settings->integer('search.max_results_per_page'),
        );
    }

    public function suggestionLimit(): int
    {
        return min($this->settings->integer('search.suggestion_limit'), 25);
    }

    public function logsQueries(): bool
    {
        return $this->settings->boolean('search.log_queries');
    }

    public function usesV2Presentation(): bool
    {
        return $this->settings->boolean('features.public_search_v2');
    }
}
