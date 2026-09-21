<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageCompositionState;
use App\Enums\PageTemplateType;
use App\Models\Concerns\HasBinaryUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property PageTemplateType $template_type
 * @property PageCompositionState $state
 * @property string $page_key
 * @property string $locale
 * @property int $version_no
 * @property int $lock_version
 * @property string $content_hash
 * @property CarbonImmutable|null $published_at
 * @property Collection<int, PageSection> $sections
 */
final class PageComposition extends Model
{
    use HasBinaryUuid, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'template_type' => PageTemplateType::class,
            'state' => PageCompositionState::class,
            'published_at' => 'immutable_datetime',
            'publish_at' => 'immutable_datetime',
            'unpublish_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ContentItem, $this> */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }

    /** @return BelongsTo<PageTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PageTemplate::class, 'template_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<PageSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function isEditable(): bool
    {
        return $this->state->editable();
    }
}
