<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ServiceVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceVersion */
final class ServiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'service_id' => (string) $this->service_id,
            'locale' => $this->locale,
            'slug' => $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'approach' => $this->approach,
            'deliverables' => $this->deliverables,
            'benefits' => $this->benefits,
        ];
    }
}
