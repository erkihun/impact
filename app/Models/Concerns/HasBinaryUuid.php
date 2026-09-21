<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

/**
 * UUIDv7 model identity.
 *
 * The application-facing representation is a canonical UUID string. The
 * BinaryUuid cast is available for the MySQL binary-key rollout documented in
 * the implementation discrepancy register.
 */
trait HasBinaryUuid
{
    use HasUuids;

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function getIncrementing(): bool
    {
        return false;
    }
}
