<?php

declare(strict_types=1);

namespace App\Data\Media;

final readonly class PrivateDownloadAuditData
{
    public function __construct(
        public string $subjectType,
        public string $subjectId,
        public string $mediaAssetId,
        public string $actorId,
        public string $correlationId,
        public string $classification,
    ) {}
}
