<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RetentionRunMode;
use App\Enums\RetentionRunStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RetentionRun extends BaseModel
{
    protected function casts(): array
    {
        return [
            'mode' => RetentionRunMode::class,
            'status' => RetentionRunStatus::class,
            'failures' => 'array',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
