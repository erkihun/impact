<?php

declare(strict_types=1);

namespace App\Data\Identity;

use App\Enums\UserStatus;

final readonly class UpdateUserData
{
    /** @param list<string> $roleIds */
    public function __construct(
        public string $userId,
        public string $name,
        public string $email,
        public string $locale,
        public UserStatus $status,
        public ?string $expiresAt,
        public array $roleIds,
        public string $actorId,
        public string $correlationId,
    ) {}
}
