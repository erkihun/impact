<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Identity\UpdateRoleData;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Identity\RoleAssignmentGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateRoleAction
{
    public function __construct(
        private AuditRecorder $audit,
        private RoleAssignmentGuard $roleGuard,
    ) {}

    public function execute(User $actor, UpdateRoleData $data): Role
    {
        if (! $actor->hasPermission('roles.manage')) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $data): Role {
            $role = Role::query()->with(['permissions', 'users'])->lockForUpdate()->findOrFail($data->roleId);
            $permissions = Permission::query()->whereKey($data->permissionIds)->get();
            if ($permissions->count() !== count($data->permissionIds)) {
                throw ValidationException::withMessages([
                    'permissions' => __('One or more selected permissions are invalid.'),
                ]);
            }
            $this->roleGuard->assertCanGrantPermissions($actor, $permissions);
            if ($role->users->contains($actor)
                && ! $permissions->contains('code', 'roles.manage')
                && ! $actor->roles()->whereKeyNot($role->id)
                    ->whereHas('permissions', fn ($query) => $query->where('code', 'roles.manage'))
                    ->exists()) {
                throw ValidationException::withMessages([
                    'permissions' => __('You cannot remove your own role-management access.'),
                ]);
            }

            $beforeHash = hash('sha256', $role->toJson().$role->permissions->pluck('id')->sort()->join('|'));
            $role->update(['name' => $data->name]);
            $role->permissions()->sync($permissions->pluck('id')->all());
            User::query()->whereHas('roles', fn ($query) => $query->whereKey($role->id))
                ->increment('session_version');
            DB::table('sessions')->whereIn('user_id', $role->users->pluck('id'))->delete();
            $this->audit->record(new AuditData(
                action: 'identity.role_updated',
                auditableType: Role::class,
                auditableId: $role->id,
                actorId: $data->actorId,
                correlationId: $data->correlationId,
                beforeHash: $beforeHash,
                afterHash: hash('sha256', $role->fresh()->toJson().$permissions->pluck('id')->sort()->join('|')),
                metadata: ['permissions' => $permissions->pluck('code')->sort()->values()->all()],
            ));

            return $role->refresh()->load('permissions');
        }, attempts: 3);
    }
}
