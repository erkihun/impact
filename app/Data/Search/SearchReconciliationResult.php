<?php

declare(strict_types=1);

namespace App\Data\Search;

final readonly class SearchReconciliationResult
{
    public function __construct(
        public int $upserted,
        public int $deleted,
    ) {}
}
