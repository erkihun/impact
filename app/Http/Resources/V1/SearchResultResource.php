<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\SearchDocument;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SearchDocument */
final class SearchResultResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $publishedAt = $this->getRawOriginal('published_at');

        return [
            'id' => (string) $this->id,
            'type' => $this->searchable_type,
            'title' => $this->title,
            'summary' => $this->summary,
            'url' => $this->url,
            'published_at' => $publishedAt === null
                ? null
                : CarbonImmutable::parse((string) $publishedAt)->toAtomString(),
        ];
    }
}
