<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasBinaryUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $media_asset_id
 * @property string $role
 * @property int $sort_order
 * @property string|null $focal_x
 * @property string|null $focal_y
 * @property bool $decorative
 * @property string|null $caption
 * @property MediaAsset $asset
 */
final class PageSectionMedia extends Model
{
    use HasBinaryUuid;

    public $timestamps = false;

    protected $table = 'page_section_media';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['decorative' => 'boolean', 'focal_x' => 'decimal:4', 'focal_y' => 'decimal:4'];
    }

    /** @return BelongsTo<PageSectionVersion, $this> */
    public function sectionVersion(): BelongsTo
    {
        return $this->belongsTo(PageSectionVersion::class, 'page_section_version_id');
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}
