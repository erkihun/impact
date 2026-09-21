<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Identity\AcceptInvitationData;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class AcceptUserInvitationAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(AcceptInvitationData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $invitation = UserInvitation::query()
                ->with('user')
                ->where('token_hash', hash('sha256', $data->token))
                ->lockForUpdate()
                ->first();

            if ($invitation === null || ! $invitation->isUsable()) {
                throw ValidationException::withMessages([
                    'invitation' => __('This invitation is invalid or has expired.'),
                ]);
            }

            $user = User::query()->with('roles')->lockForUpdate()->findOrFail($invitation->user_id);
            if ($user->status !== UserStatus::Invited) {
                throw ValidationException::withMessages([
                    'invitation' => __('This invitation has already been completed.'),
                ]);
            }

            $user->forceFill([
                'name' => $data->name,
                'password' => Hash::make($data->password),
                'status' => UserStatus::Active,
                'email_verified_at' => now('UTC'),
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'session_version' => $user->session_version + 1,
            ])->save();
            $invitation->forceFill(['accepted_at' => now('UTC')])->save();

            $this->audit->record(new AuditData(
                action: 'identity.invitation_accepted',
                auditableType: User::class,
                auditableId: $user->id,
                actorId: $user->id,
                correlationId: $data->correlationId,
                afterHash: hash('sha256', $user->toJson().$user->roles->pluck('id')->sort()->join('|')),
                metadata: ['invitation_id' => $invitation->id],
            ));

            return $user->refresh();
        }, attempts: 3);
    }
}
