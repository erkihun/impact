<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class Service extends BaseModel
{
    protected function casts(): array
    {
        return ['featured' => 'boolean'];
    }

    /** @return HasMany<ServiceVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ServiceVersion::class);
    }
}
