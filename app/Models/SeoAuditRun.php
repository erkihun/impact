<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

/**
 * @property int $blocking_count
 * @property int $warning_count
 * @property int $information_count
 * @property array<string, mixed> $metrics
 * @property list<array{code: string, severity: string, message: string, url: string|null, subject: string|null}> $issues
 * @property string $trigger
 * @property CarbonImmutable|null $created_at
 */
final class SeoAuditRun extends BaseModel
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'issues' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
