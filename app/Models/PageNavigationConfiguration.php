<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasBinaryUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $location
 * @property string $locale
 * @property string $label
 * @property string|null $description
 * @property string $icon
 * @property string $route_name
 * @property array<string, mixed>|null $route_parameters
 * @property int $sort_order
 * @property bool $enabled
 * @property CarbonImmutable|null $visible_from
 * @property CarbonImmutable|null $visible_until
 * @property Collection<int, PageNavigationConfiguration> $children
 */
final class PageNavigationConfiguration extends Model
{
    use HasBinaryUuid;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'route_parameters' => 'array',
            'enabled' => 'boolean',
            'visible_from' => 'immutable_datetime',
            'visible_until' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PageNavigationConfiguration, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<PageNavigationConfiguration, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
