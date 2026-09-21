<?php

declare(strict_types=1);

namespace App\Models;

final class SearchDocument extends BaseModel
{
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'published_at' => 'immutable_datetime',
        ];
    }
}
