<?php

declare(strict_types=1);

namespace App\Models;

final class Office extends BaseModel
{
    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }
}
