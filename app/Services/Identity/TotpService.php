<?php

declare(strict_types=1);

namespace App\Services\Identity;

use PragmaRX\Google2FA\Google2FA;

final readonly class TotpService
{
    public function __construct(private Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return (string) $this->google2fa->generateSecretKey(32);
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code, 1) !== false;
    }

    public function provisioningUri(string $email, string $secret): string
    {
        return (string) $this->google2fa->getQRCodeUrl(
            (string) config('app.name'),
            $email,
            $secret,
        );
    }
}
