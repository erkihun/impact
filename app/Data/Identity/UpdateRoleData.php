<?php

declare(strict_types=1);

namespace App\Data\Identity;

final readonly class UpdateRoleData
{
    /** @param list<string> $permissionIds */
    public function __construct(
        public string $roleId,
        public string $name,
        public array $permissionIds,
        public string $actorId,
        public string $correlationId,
    ) {}
}
