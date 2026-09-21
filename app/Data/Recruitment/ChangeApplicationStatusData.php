<?php

declare(strict_types=1);

namespace App\Data\Recruitment;

use App\Enums\ApplicationStatus;

final readonly class ChangeApplicationStatusData
{
    public function __construct(
        public string $applicationId,
        public ApplicationStatus $to,
        public string $reason,
        public string $actorId,
        public string $correlationId,
    ) {}
}
