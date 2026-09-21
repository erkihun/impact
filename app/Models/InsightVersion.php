<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentWorkflowState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InsightVersion extends BaseModel
{
    protected function casts(): array
    {
        return ['workflow_state' => ContentWorkflowState::class];
    }

    /** @return BelongsTo<Insight, $this> */
    public function insight(): BelongsTo
    {
        return $this->belongsTo(Insight::class);
    }

    /** @param Builder<InsightVersion> $query */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('workflow_state', ContentWorkflowState::Published->value)
            ->whereHas('insight', fn (Builder $insight): Builder => $insight
                ->where('status', 'published')
                ->where(fn (Builder $expiry): Builder => $expiry
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now('UTC'))));
    }
}
