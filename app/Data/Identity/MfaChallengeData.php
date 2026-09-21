<?php

declare(strict_types=1);

namespace App\Data\Identity;

final readonly class MfaChallengeData
{
    public function __construct(
        public string $userId,
        public string $code,
        public string $correlationId,
    ) {}
}
