<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentWorkflowState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ServiceVersion extends BaseModel
{
    protected function casts(): array
    {
        return ['deliverables' => 'array', 'workflow_state' => ContentWorkflowState::class];
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @param Builder<ServiceVersion> $query */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('workflow_state', ContentWorkflowState::Published->value)
            ->whereHas('service', fn (Builder $service): Builder => $service->where('status', 'published'));
    }
}
