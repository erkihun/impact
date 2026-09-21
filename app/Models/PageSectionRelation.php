<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasBinaryUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $relation_type
 * @property string $related_type
 * @property string $related_id
 * @property int $sort_order
 * @property array<string, mixed>|null $metadata
 */
final class PageSectionRelation extends Model
{
    use HasBinaryUuid;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    /** @return BelongsTo<PageSectionVersion, $this> */
    public function sectionVersion(): BelongsTo
    {
        return $this->belongsTo(PageSectionVersion::class, 'page_section_version_id');
    }

    /** @return MorphTo<Model, $this> */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
