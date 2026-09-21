<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PublicReferenceGenerator;
use Illuminate\Support\Str;

final readonly class SecurePublicReferenceGenerator implements PublicReferenceGenerator
{
    public function generate(string $prefix): string
    {
        $normalizedPrefix = Str::upper(Str::substr(
            preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?: 'REF',
            0,
            4,
        ));

        return sprintf(
            '%s-%s-%s',
            $normalizedPrefix,
            now('UTC')->format('ymd'),
            Str::upper(Str::random(12)),
        );
    }
}
