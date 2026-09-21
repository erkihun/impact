<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Identity\MfaChallengeData;
use App\Models\User;
use App\Services\Identity\TotpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class VerifyMfaChallengeAction
{
    public function __construct(
        private TotpService $totp,
        private AuditRecorder $audit,
    ) {}

    public function execute(MfaChallengeData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->lockForUpdate()->findOrFail($data->userId);
            $secret = $user->mfa_secret;
            $recoveryCodes = $user->mfa_recovery_codes ?? [];
            $normalizedCode = Str::upper($data->code);
            $recoveryIndex = collect($recoveryCodes)->search(
                static fn (string $hash): bool => Hash::check($normalizedCode, $hash),
            );
            $validTotp = is_string($secret) && preg_match('/^\d{6}$/', $data->code) === 1
                && $this->totp->verify($secret, $data->code);

            if (! $validTotp && $recoveryIndex === false) {
                throw ValidationException::withMessages(['code' => __('The verification code is invalid.')]);
            }
            if ($recoveryIndex !== false) {
                unset($recoveryCodes[$recoveryIndex]);
                $user->forceFill(['mfa_recovery_codes' => array_values($recoveryCodes)])->save();
            }
            $this->audit->record(new AuditData(
                action: $recoveryIndex === false ? 'identity.mfa_verified' : 'identity.mfa_recovery_used',
                auditableType: User::class,
                auditableId: $user->id,
                actorId: $user->id,
                correlationId: $data->correlationId,
            ));

            return $user;
        }, attempts: 3);
    }
}
