<?php

declare(strict_types=1);

namespace App\Data\Identity;

final readonly class AcceptInvitationData
{
    public function __construct(
        public string $token,
        public string $name,
        public string $password,
        public string $correlationId,
    ) {}
}
