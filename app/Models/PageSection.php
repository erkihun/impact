<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasBinaryUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $stable_key
 * @property string $editor_label
 * @property int $sort_order
 * @property bool $required
 * @property bool $locked
 * @property PageComposition $composition
 * @property PageSectionVersion|null $currentVersion
 */
final class PageSection extends Model
{
    use HasBinaryUuid, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['required' => 'boolean', 'locked' => 'boolean'];
    }

    /** @return BelongsTo<PageComposition, $this> */
    public function composition(): BelongsTo
    {
        return $this->belongsTo(PageComposition::class, 'page_composition_id');
    }

    /** @return BelongsTo<PageSectionVersion, $this> */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(PageSectionVersion::class, 'current_version_id');
    }

    /** @return HasMany<PageSectionVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(PageSectionVersion::class);
    }
}
