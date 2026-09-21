<?php

declare(strict_types=1);

namespace App\Data\Privacy;

use App\Enums\RetentionRunMode;

final readonly class ExecuteRetentionData
{
    public function __construct(
        public RetentionRunMode $mode,
        public string $policyVersion,
        public string $correlationId,
        public ?string $approvedBy,
        public int $limit,
    ) {}
}
