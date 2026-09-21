<?php

declare(strict_types=1);

namespace App\Data\Content;

final readonly class UpdateContentData
{
    public function __construct(
        public string $contentItemId,
        public string $expectedCurrentVersionId,
        public string $actorId,
        public string $title,
        public ?string $summary,
        public string $body,
        public string $correlationId,
    ) {}
}
