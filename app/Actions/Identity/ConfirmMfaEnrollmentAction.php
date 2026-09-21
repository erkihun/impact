<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Models\User;
use App\Services\Identity\TotpService;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class ConfirmMfaEnrollmentAction
{
    public function __construct(
        private TotpService $totp,
        private AuditRecorder $audit,
        private EffectiveSettings $settings,
    ) {}

    /** @return list<string> */
    public function execute(User $user, string $code, string $correlationId): array
    {
        if ($user->mfa_secret === null || ! $this->totp->verify($user->mfa_secret, $code)) {
            throw ValidationException::withMessages(['code' => __('The verification code is invalid.')]);
        }

        return DB::transaction(function () use ($user, $correlationId): array {
            $codes = collect(range(1, $this->settings->integer('authentication.recovery_code_count')))
                ->map(fn (): string => Str::upper(Str::random(5).'-'.Str::random(5)))
                ->values()
                ->all();
            $user->forceFill([
                'mfa_recovery_codes' => array_map(
                    static fn (string $recoveryCode): string => Hash::make($recoveryCode),
                    $codes,
                ),
                'mfa_confirmed_at' => now('UTC'),
                'session_version' => $user->session_version + 1,
            ])->save();
            $this->audit->record(new AuditData(
                action: 'identity.mfa_enabled',
                auditableType: User::class,
                auditableId: $user->id,
                actorId: $user->id,
                correlationId: $correlationId,
                afterHash: hash('sha256', $user->id.'|enabled'),
            ));

            return $codes;
        }, attempts: 3);
    }
}
