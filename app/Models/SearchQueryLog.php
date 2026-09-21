<?php

declare(strict_types=1);

namespace App\Models;

final class SearchQueryLog extends BaseModel
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
