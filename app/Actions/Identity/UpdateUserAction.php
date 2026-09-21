<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Identity\UpdateUserData;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\Identity\RoleAssignmentGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateUserAction
{
    public function __construct(
        private AuditRecorder $audit,
        private RoleAssignmentGuard $roleGuard,
    ) {}

    public function execute(User $actor, UpdateUserData $data): User
    {
        if (! $actor->hasPermission('users.manage')) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $data): User {
            $target = User::query()->with('roles')->lockForUpdate()->findOrFail($data->userId);
            $roles = Role::query()->whereKey($data->roleIds)->get();
            if ($roles->count() !== count($data->roleIds)) {
                throw ValidationException::withMessages(['roles' => __('One or more selected roles are invalid.')]);
            }
            $this->roleGuard->assertCanAssignRoles($actor, $roles);
            $administratorCodes = ['administrator', 'super_administrator'];
            $wasAdministrator = $target->roles->contains(
                fn (Role $role): bool => in_array($role->code, $administratorCodes, true),
            );
            $willBeAdministrator = $roles->contains(
                fn (Role $role): bool => in_array($role->code, $administratorCodes, true),
            );
            $removesAdministrator = $wasAdministrator
                && ($data->status !== UserStatus::Active || ! $willBeAdministrator);

            if ($target->is($actor) && ($data->status !== UserStatus::Active || ! $willBeAdministrator)) {
                throw ValidationException::withMessages([
                    'status' => __('You cannot disable or remove your own administrative access.'),
                ]);
            }
            if ($removesAdministrator && ! User::query()
                ->whereKeyNot($target->id)
                ->where('status', UserStatus::Active)
                ->whereHas('roles', fn ($query) => $query->whereIn('code', $administratorCodes))
                ->exists()) {
                throw ValidationException::withMessages([
                    'status' => __('The only active administrator cannot be disabled.'),
                ]);
            }

            $beforeHash = hash('sha256', $target->toJson().$target->roles->pluck('id')->sort()->join('|'));
            $target->forceFill([
                'name' => $data->name,
                'email' => $data->email,
                'locale' => $data->locale,
                'status' => $data->status,
                'expires_at' => $data->expiresAt,
                'session_version' => $target->session_version + 1,
            ])->save();
            $target->roles()->sync($roles->pluck('id')->all());
            DB::table('sessions')->where('user_id', $target->id)->delete();
            $this->audit->record(new AuditData(
                action: 'identity.user_updated',
                auditableType: User::class,
                auditableId: $target->id,
                actorId: $data->actorId,
                correlationId: $data->correlationId,
                beforeHash: $beforeHash,
                afterHash: hash('sha256', $target->fresh()->toJson().$roles->pluck('id')->sort()->join('|')),
                metadata: ['status' => $data->status->value, 'roles' => $roles->pluck('code')->sort()->values()->all()],
            ));

            return $target->refresh()->load('roles');
        }, attempts: 3);
    }
}
