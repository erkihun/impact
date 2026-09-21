<?php

declare(strict_types=1);

namespace App\Models;

final class SeoMetadata extends BaseModel
{
    protected function casts(): array
    {
        return ['structured_data' => 'array'];
    }
}
