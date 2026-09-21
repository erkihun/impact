<?php

declare(strict_types=1);

namespace App\Data\Media;

final readonly class ApproveMediaData
{
    public function __construct(
        public string $mediaAssetId,
        public string $actorId,
        public string $correlationId,
    ) {}
}
