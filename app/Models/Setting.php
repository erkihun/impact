<?php

declare(strict_types=1);

namespace App\Models;

final class Setting extends BaseModel
{
    protected function casts(): array
    {
        return [
            'last_effective_at' => 'immutable_datetime',
            'value' => 'array',
        ];
    }
}
