<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final readonly class RoleAssignmentGuard
{
    /** @param Collection<int, Role> $roles */
    public function assertCanAssignRoles(User $actor, Collection $roles): void
    {
        if ($this->isSuperAdministrator($actor)) {
            return;
        }

        if ($roles->contains('code', 'super_administrator')) {
            throw ValidationException::withMessages([
                'roles' => __('You cannot assign a role above your delegated authority.'),
            ]);
        }

        $actorPermissions = $actor->roles()
            ->with('permissions:id,code')
            ->get()
            ->flatMap->permissions
            ->pluck('code')
            ->unique();
        $requestedPermissions = $roles
            ->loadMissing('permissions:id,code')
            ->flatMap->permissions
            ->pluck('code')
            ->unique();

        if ($requestedPermissions->diff($actorPermissions)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'roles' => __('You cannot assign permissions that you do not hold.'),
            ]);
        }
    }

    /** @param Collection<int, Permission> $permissions */
    public function assertCanGrantPermissions(User $actor, Collection $permissions): void
    {
        if ($this->isSuperAdministrator($actor)) {
            return;
        }

        $actorPermissionCodes = $actor->roles()
            ->with('permissions:id,code')
            ->get()
            ->flatMap->permissions
            ->pluck('code')
            ->unique();

        if ($permissions->pluck('code')->diff($actorPermissionCodes)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'permissions' => __('You cannot grant permissions that you do not hold.'),
            ]);
        }
    }

    private function isSuperAdministrator(User $actor): bool
    {
        return $actor->roles()->where('code', 'super_administrator')->exists();
    }
}
