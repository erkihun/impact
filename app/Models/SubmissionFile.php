<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SubmissionFile extends BaseModel
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'scanned_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    /** @return BelongsTo<EngagementSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(EngagementSubmission::class, 'submission_id');
    }
}
