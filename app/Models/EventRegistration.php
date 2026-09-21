<?php

declare(strict_types=1);

namespace App\Models;

final class EventRegistration extends BaseModel
{
    protected function casts(): array
    {
        return [
            'registered_at' => 'immutable_datetime',
            'retention_until' => 'immutable_date',
            'retention_processed_at' => 'immutable_datetime',
        ];
    }
}
