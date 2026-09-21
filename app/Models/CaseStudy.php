<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientDisplayMode;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CaseStudy extends BaseModel
{
    protected function casts(): array
    {
        return [
            'client_display_mode' => ClientDisplayMode::class,
            'client_consent_at' => 'immutable_datetime',
            'featured' => 'boolean',
        ];
    }

    /** @return HasMany<CaseStudyVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(CaseStudyVersion::class);
    }
}
