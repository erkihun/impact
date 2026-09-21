<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PublicationSchedule extends BaseModel
{
    protected function casts(): array
    {
        return [
            'publish_at' => 'immutable_datetime',
            'unpublish_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ContentItem, $this> */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }

    /** @return BelongsTo<ContentVersion, $this> */
    public function contentVersion(): BelongsTo
    {
        return $this->belongsTo(ContentVersion::class);
    }
}
