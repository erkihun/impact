<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Editor-supplied SEO overrides for one public subject: a static page key
 * (subject_type "page") or a public resource identity (subject_type is a
 * PublicResourceType value and subject_key the resource's stable id).
 *
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $canonical_path
 * @property string|null $robots
 * @property bool $include_in_sitemap
 * @property string|null $social_title
 * @property string|null $social_description
 * @property string|null $social_image_media_id
 * @property list<string>|null $secondary_topics
 * @property list<string>|null $geographic_relevance
 */
final class SeoMetadata extends BaseModel
{
    protected $table = 'seo_metadata';

    protected function casts(): array
    {
        return [
            'include_in_sitemap' => 'boolean',
            'secondary_topics' => 'array',
            'geographic_relevance' => 'array',
        ];
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function socialImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'social_image_media_id');
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
