<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentWorkflowState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ExpertVersion extends BaseModel
{
    protected function casts(): array
    {
        return ['qualifications' => 'array', 'languages' => 'array', 'workflow_state' => ContentWorkflowState::class];
    }

    /** @return BelongsTo<Expert, $this> */
    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    /** @param Builder<ExpertVersion> $query */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->whereHas('expert', fn (Builder $expert): Builder => $expert
            ->where('status', 'published')
            ->whereNotNull('publication_authorized_at'));
    }
}
