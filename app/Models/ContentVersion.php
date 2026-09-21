<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentWorkflowState;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property array<string, mixed> $body */
final class ContentVersion extends BaseModel
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'workflow_state' => ContentWorkflowState::class,
        ];
    }

    /** @return BelongsTo<ContentItem, $this> */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
