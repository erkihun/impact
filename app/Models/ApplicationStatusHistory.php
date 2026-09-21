<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStatus;

final class ApplicationStatusHistory extends BaseModel
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'from_status' => ApplicationStatus::class,
            'to_status' => ApplicationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        self::updating(fn (): never => throw new \LogicException('Application history is append-only.'));
        self::deleting(fn (): never => throw new \LogicException('Application history is append-only.'));
    }
}
