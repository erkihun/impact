<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentSelectionMode;
use App\Enums\PageSectionType;
use App\Enums\VisibilityRule;
use App\Models\Concerns\HasBinaryUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property PageSectionType $type
 * @property string $locale
 * @property string $variant
 * @property array<string, mixed> $content
 * @property array<string, mixed> $presentation
 * @property bool $enabled
 * @property VisibilityRule $visibility_rule
 * @property ContentSelectionMode $selection_mode
 * @property int|null $maximum_items
 * @property CarbonImmutable|null $visible_from
 * @property CarbonImmutable|null $visible_until
 * @property Collection<int, PageSectionRelation> $sectionRelations
 * @property Collection<int, PageSectionMedia> $media
 * @property Collection<int, PageSectionAction> $actions
 */
final class PageSectionVersion extends Model
{
    use HasBinaryUuid;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => PageSectionType::class,
            'content' => 'array',
            'presentation' => 'array',
            'enabled' => 'boolean',
            'visibility_rule' => VisibilityRule::class,
            'selection_mode' => ContentSelectionMode::class,
            'visible_from' => 'immutable_datetime',
            'visible_until' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PageSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(PageSection::class, 'page_section_id');
    }

    /** @return HasMany<PageSectionRelation, $this> */
    public function sectionRelations(): HasMany
    {
        return $this->hasMany(PageSectionRelation::class)->orderBy('sort_order');
    }

    /** @return HasMany<PageSectionMedia, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(PageSectionMedia::class)->orderBy('sort_order');
    }

    /** @return HasMany<PageSectionAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(PageSectionAction::class)->orderBy('sort_order');
    }
}
