<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentType;
use App\Enums\ContentWorkflowState;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ContentItem extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ContentType::class,
            'status' => ContentWorkflowState::class,
            'published_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<ContentVersion, $this> */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ContentVersion::class, 'current_version_id');
    }

    /** @return HasMany<ContentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ContentVersion::class);
    }

    /** @return HasMany<WorkflowEvent, $this> */
    public function workflowEvents(): HasMany
    {
        return $this->hasMany(WorkflowEvent::class);
    }
}
