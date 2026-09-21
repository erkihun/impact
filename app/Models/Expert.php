<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Expert extends BaseModel
{
    protected function casts(): array
    {
        return ['public_email_enabled' => 'boolean', 'publication_authorized_at' => 'immutable_datetime'];
    }

    /** @return HasMany<ExpertVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ExpertVersion::class);
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function profileMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'profile_media_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
