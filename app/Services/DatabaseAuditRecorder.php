<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Models\AuditEvent;

final readonly class DatabaseAuditRecorder implements AuditRecorder
{
    public function record(AuditData $data): AuditEvent
    {
        return AuditEvent::query()->create([
            'actor_id' => $data->actorId,
            'action' => $data->action,
            'auditable_type' => $data->auditableType,
            'auditable_id' => $data->auditableId,
            'before_hash' => $data->beforeHash,
            'after_hash' => $data->afterHash,
            'metadata' => $data->metadata,
            'correlation_id' => $data->correlationId,
            'ip_hash' => $data->ipHash,
        ]);
    }
}
