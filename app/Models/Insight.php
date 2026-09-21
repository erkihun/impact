<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class Insight extends BaseModel
{
    protected function casts(): array
    {
        return ['featured' => 'boolean', 'published_at' => 'immutable_datetime'];
    }

    /** @return HasMany<InsightVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(InsightVersion::class);
    }
}
