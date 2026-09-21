<?php

declare(strict_types=1);

namespace App\Models;

final class SearchSynonym extends BaseModel
{
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
