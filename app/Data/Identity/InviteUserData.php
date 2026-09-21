<?php

declare(strict_types=1);

namespace App\Data\Identity;

final readonly class InviteUserData
{
    /** @param list<string> $roleIds */
    public function __construct(
        public string $name,
        public string $email,
        public string $locale,
        public array $roleIds,
        public string $actorId,
        public string $correlationId,
    ) {}
}
