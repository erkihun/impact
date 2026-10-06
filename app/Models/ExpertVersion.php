<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentWorkflowState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property list<string>|null $qualifications
 * @property list<string>|null $languages
 */
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
        // Draft revisions of a published profile must never be served.
        $query->where('workflow_state', ContentWorkflowState::Published->value)
            ->whereHas('expert', fn (Builder $expert): Builder => $expert
                ->where('status', 'published')
                ->whereNotNull('publication_authorized_at'));
    }
}
