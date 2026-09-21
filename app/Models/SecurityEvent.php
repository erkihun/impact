<?php

declare(strict_types=1);

namespace App\Models;

final class SecurityEvent extends BaseModel
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    protected static function booted(): void
    {
        self::updating(fn (): never => throw new \LogicException('Security events are append-only.'));
        self::deleting(fn (): never => throw new \LogicException('Security events are append-only.'));
    }
}
