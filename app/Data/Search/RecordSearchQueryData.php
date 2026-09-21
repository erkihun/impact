<?php

declare(strict_types=1);

namespace App\Data\Search;

final readonly class RecordSearchQueryData
{
    public function __construct(
        public string $locale,
        public string $query,
        public int $resultCount,
    ) {}
}
