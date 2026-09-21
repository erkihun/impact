<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentWorkflowState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CaseStudyVersion extends BaseModel
{
    protected function casts(): array
    {
        return ['metrics' => 'array', 'workflow_state' => ContentWorkflowState::class];
    }

    /** @return BelongsTo<CaseStudy, $this> */
    public function caseStudy(): BelongsTo
    {
        return $this->belongsTo(CaseStudy::class);
    }

    /** @param Builder<CaseStudyVersion> $query */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('workflow_state', ContentWorkflowState::Published->value)
            ->whereHas('caseStudy', fn (Builder $caseStudy): Builder => $caseStudy
                ->where('status', 'published')
                ->where('client_display_mode', '!=', 'confidential')
                ->where(fn (Builder $visibility): Builder => $visibility
                    ->where('client_display_mode', 'anonymized')
                    ->orWhereNotNull('client_consent_at')));
    }
}
