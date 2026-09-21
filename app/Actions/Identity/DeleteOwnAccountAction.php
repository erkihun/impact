<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DeleteOwnAccountAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(User $actor, string $correlationId): void
    {
        DB::transaction(function () use ($actor, $correlationId): void {
            $user = User::query()->with('roles')->lockForUpdate()->findOrFail($actor->id);
            if ($user->roles->isNotEmpty()) {
                $exception = ValidationException::withMessages([
                    'password' => __('Staff accounts must be disabled by an authorized administrator and cannot self-delete.'),
                ]);
                $exception->errorBag = 'userDeletion';

                throw $exception;
            }

            $this->audit->record(new AuditData(
                action: 'identity.account_self_deleted',
                auditableType: User::class,
                auditableId: $user->id,
                actorId: $user->id,
                correlationId: $correlationId,
                beforeHash: hash('sha256', $user->toJson()),
            ));
            $user->delete();
        }, attempts: 3);
    }
}
