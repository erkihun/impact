<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

/**
 * @property string $fingerprint
 * @property string $url
 * @property string $source_url
 * @property string $source_label
 * @property string|null $link_text
 * @property int|null $status_code
 * @property string $result
 * @property string $severity
 * @property string $resolution_status
 * @property CarbonImmutable $first_detected_at
 * @property CarbonImmutable $last_checked_at
 */
final class SeoLinkCheck extends BaseModel
{
    protected function casts(): array
    {
        return [
            'external' => 'boolean',
            'status_code' => 'integer',
            'first_detected_at' => 'immutable_datetime',
            'last_checked_at' => 'immutable_datetime',
        ];
    }
}
