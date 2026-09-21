<?php

declare(strict_types=1);

namespace App\Models;

final class Locale extends BaseModel
{
    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'is_default' => 'boolean'];
    }
}
