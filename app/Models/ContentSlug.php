<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContentSlug extends Model
{
    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $primaryKey = null;

    /** @var list<string> */
    protected $fillable = [
        'content_item_id',
        'locale',
        'slug',
    ];

    /** @return BelongsTo<ContentItem, $this> */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }
}
