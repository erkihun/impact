<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Identity\InviteUserData;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\Identity\UserInvitationNotification;
use App\Services\Identity\RoleAssignmentGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class InviteUserAction
{
    public function __construct(
        private AuditRecorder $audit,
        private RoleAssignmentGuard $roleGuard,
    ) {}

    public function execute(User $actor, InviteUserData $data): UserInvitation
    {
        if (! $actor->hasPermission('users.manage')) {
            throw new AuthorizationException;
        }

        $rawToken = Str::random(64);
        $invitation = DB::transaction(function () use ($actor, $data, $rawToken): UserInvitation {
            $roles = Role::query()->with('permissions:id,code')->whereKey($data->roleIds)->get();
            if ($roles->count() !== count($data->roleIds)) {
                throw ValidationException::withMessages([
                    'roles' => __('One or more selected roles are invalid.'),
                ]);
            }
            $this->roleGuard->assertCanAssignRoles($actor, $roles);

            $existing = User::query()->where('email', $data->email)->lockForUpdate()->first();
            if ($existing !== null && $existing->status !== UserStatus::Invited) {
                throw ValidationException::withMessages([
                    'email' => __('An active or retained account already uses this email address.'),
                ]);
            }

            $user = $existing ?? User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make(Str::random(64)),
                'locale' => $data->locale,
                'status' => UserStatus::Invited,
            ]);
            $user->forceFill([
                'name' => $data->name,
                'locale' => $data->locale,
                'status' => UserStatus::Invited,
                'email_verified_at' => null,
                'session_version' => $user->session_version + 1,
            ])->save();
            $user->roles()->sync($roles->pluck('id')->all());

            $invitation = UserInvitation::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'invited_by' => $actor->id,
                    'token_hash' => hash('sha256', $rawToken),
                    'role_ids' => $roles->pluck('id')->values()->all(),
                    'locale' => $data->locale,
                    'expires_at' => now('UTC')->addDays(7),
                    'accepted_at' => null,
                    'revoked_at' => null,
                ],
            );

            $this->audit->record(new AuditData(
                action: 'identity.user_invited',
                auditableType: User::class,
                auditableId: $user->id,
                actorId: $data->actorId,
                correlationId: $data->correlationId,
                afterHash: hash('sha256', $user->toJson().$roles->pluck('id')->sort()->join('|')),
                metadata: [
                    'roles' => $roles->pluck('code')->sort()->values()->all(),
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                ],
            ));

            return $invitation->load('user');
        }, attempts: 3);

        DB::afterCommit(fn () => $invitation->user->notify(
            (new UserInvitationNotification($rawToken, $invitation->expires_at))->locale($invitation->locale),
        ));

        return $invitation;
    }
}
