<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentWorkflowState;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WorkflowEvent extends BaseModel
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'from_state' => ContentWorkflowState::class,
            'to_state' => ContentWorkflowState::class,
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

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function booted(): void
    {
        self::updating(fn (): never => throw new \LogicException('Workflow events are append-only.'));
        self::deleting(fn (): never => throw new \LogicException('Workflow events are append-only.'));
    }
}
