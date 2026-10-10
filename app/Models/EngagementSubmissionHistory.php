<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EngagementSubmissionHistory extends BaseModel
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
            'from_status' => SubmissionStatus::class,
            'to_status' => SubmissionStatus::class,
        ];
    }

    protected static function booted(): void
    {
        self::updating(fn (): never => throw new \LogicException('Submission history is append-only.'));
        self::deleting(fn (): never => throw new \LogicException('Submission history is append-only.'));
    }
}
