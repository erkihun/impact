<?php

declare(strict_types=1);

namespace App\Data\Content;

use App\Enums\ContentType;

final readonly class CreateContentData
{
    /** @param array<string, mixed> $body */
    public function __construct(
        public ContentType $type,
        public string $ownerId,
        public string $locale,
        public string $slug,
        public string $title,
        public array $body,
        public string $correlationId,
        public ?string $summary = null,
    ) {}
}
