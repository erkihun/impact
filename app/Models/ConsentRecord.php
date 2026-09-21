<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsentCategory;

final class ConsentRecord extends BaseModel
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'category' => ConsentCategory::class,
            'decision' => 'boolean',
            'recorded_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(fn (): never => throw new \LogicException('Consent evidence is append-only.'));
        self::deleting(fn (): never => throw new \LogicException('Consent evidence is append-only.'));
    }
}
