<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EngagementSubmission extends BaseModel
{
    protected function casts(): array
    {
        return [
            'type' => SubmissionType::class,
            'status' => SubmissionStatus::class,
            'phone_encrypted' => 'encrypted',
            'description_encrypted' => 'encrypted',
            'retention_until' => 'immutable_date',
            'retention_processed_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return HasMany<SubmissionFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class, 'submission_id');
    }

    /** @return HasMany<EngagementSubmissionHistory, $this> */
    public function history(): HasMany
    {
        return $this->hasMany(EngagementSubmissionHistory::class);
    }
}
