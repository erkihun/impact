<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Seo\RedirectOrigin;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A governed URL decision: a permanent/temporary move to a local path, or a
 * 410 Gone record (null destination) for URLs that will not return.
 *
 * @property string $source_path
 * @property string|null $destination_url
 * @property int $status_code
 */
final class Redirect extends BaseModel
{
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'status_code' => 'integer',
            'hit_count' => 'integer',
            'origin' => RedirectOrigin::class,
            'last_hit_at' => 'immutable_datetime',
        ];
    }

    public function isGone(): bool
    {
        return $this->status_code === 410;
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
