<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $expires_at
 */
final class Insight extends BaseModel
{
    protected function casts(): array
    {
        return ['featured' => 'boolean', 'published_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function primaryMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'primary_media_id');
    }

    /** @return HasMany<InsightVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(InsightVersion::class);
    }
}
