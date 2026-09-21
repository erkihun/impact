<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class Industry extends BaseModel
{
    /** @return HasMany<IndustryVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(IndustryVersion::class);
    }
}
