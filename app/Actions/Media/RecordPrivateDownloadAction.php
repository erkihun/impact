<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Media\PrivateDownloadAuditData;

final readonly class RecordPrivateDownloadAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(PrivateDownloadAuditData $data): void
    {
        $this->audit->record(new AuditData(
            action: 'private_file.downloaded',
            auditableType: $data->subjectType,
            auditableId: $data->subjectId,
            actorId: $data->actorId,
            correlationId: $data->correlationId,
            metadata: [
                'media_asset_id' => $data->mediaAssetId,
                'classification' => $data->classification,
            ],
        ));
    }
}
