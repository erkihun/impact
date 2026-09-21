<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentWorkflowState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IndustryVersion extends BaseModel
{
    protected function casts(): array
    {
        return ['workflow_state' => ContentWorkflowState::class];
    }

    /** @return BelongsTo<Industry, $this> */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /** @param Builder<IndustryVersion> $query */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('workflow_state', ContentWorkflowState::Published->value)
            ->whereHas('industry', fn (Builder $industry): Builder => $industry->where('status', 'published'));
    }
}
