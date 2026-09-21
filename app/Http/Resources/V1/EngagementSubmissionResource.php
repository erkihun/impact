<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\EngagementSubmission;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EngagementSubmission */
final class EngagementSubmissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $submittedAt = $this->getRawOriginal('submitted_at');
        $retentionUntil = $this->getRawOriginal('retention_until');

        return [
            'id' => (string) $this->id,
            'reference' => $this->reference_no,
            'type' => $this->getRawOriginal('type'),
            'status' => $this->getRawOriginal('status'),
            'locale' => $this->locale,
            'organization_name' => $this->organization_name,
            'service_id' => $this->service_id,
            'industry_id' => $this->industry_id,
            'assigned_to' => $this->assigned_to,
            'submitted_at' => $submittedAt === null
                ? null
                : CarbonImmutable::parse((string) $submittedAt)->toAtomString(),
            'retention_until' => $retentionUntil === null
                ? null
                : CarbonImmutable::parse((string) $retentionUntil)->toDateString(),
        ];
    }
}
