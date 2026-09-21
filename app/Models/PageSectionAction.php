<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ButtonVariant;
use App\Models\Concerns\HasBinaryUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $label
 * @property string $action_type
 * @property string|null $internal_route
 * @property string|null $external_url
 * @property string|null $destination_type
 * @property string|null $destination_id
 * @property ButtonVariant $button_variant
 * @property string|null $accessible_description
 * @property bool $open_new_context
 * @property int $sort_order
 */
final class PageSectionAction extends Model
{
    use HasBinaryUuid;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['button_variant' => ButtonVariant::class, 'open_new_context' => 'boolean'];
    }

    /** @return BelongsTo<PageSectionVersion, $this> */
    public function sectionVersion(): BelongsTo
    {
        return $this->belongsTo(PageSectionVersion::class, 'page_section_version_id');
    }

    /** @return MorphTo<Model, $this> */
    public function destination(): MorphTo
    {
        return $this->morphTo();
    }
}
