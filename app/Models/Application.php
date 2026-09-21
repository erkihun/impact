<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Application extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'phone_encrypted' => 'encrypted',
            'cover_letter_encrypted' => 'encrypted',
            'retention_until' => 'immutable_date',
            'retention_processed_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Vacancy, $this> */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    /** @return HasMany<ApplicationStatusHistory, $this> */
    public function history(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }

    /** @return HasMany<ApplicationFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(ApplicationFile::class);
    }
}
