<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApplicationStatusHistory extends BaseModel
{
    public const UPDATED_AT = null;

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

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
