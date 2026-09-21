<?php

declare(strict_types=1);

namespace App\Data\Content;

final readonly class RollbackContentData
{
    public function __construct(
        public string $contentItemId,
        public string $expectedCurrentVersionId,
        public string $sourceVersionId,
        public string $actorId,
        public string $reason,
        public string $correlationId,
    ) {}
}
