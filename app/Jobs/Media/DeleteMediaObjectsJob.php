<?php

declare(strict_types=1);

namespace App\Jobs\Media;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Models\MediaAsset;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class DeleteMediaObjectsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 180;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 1800];

    /** @param list<string> $mediaAssetIds */
    public function __construct(
        public readonly array $mediaAssetIds,
        public readonly string $actorId,
        public readonly string $correlationId,
    ) {
        $this->onQueue('media');
    }

    public function uniqueId(): string
    {
        $ids = $this->mediaAssetIds;
        sort($ids);

        return hash('sha256', implode('|', $ids));
    }

    public function handle(AuditRecorder $audit): void
    {
        foreach ($this->mediaAssetIds as $mediaAssetId) {
            $asset = MediaAsset::query()->with('variants')->find($mediaAssetId);
            if ($asset === null) {
                continue;
            }

            $paths = $asset->variants->pluck('path')->push($asset->path)->unique()->values();
            foreach ($paths as $path) {
                if (Storage::disk($asset->disk)->exists($path)
                    && ! Storage::disk($asset->disk)->delete($path)) {
                    throw new RuntimeException("Unable to delete restricted object for media {$asset->id}.");
                }
            }

            DB::transaction(function () use ($asset, $audit): void {
                $assetId = $asset->id;
                $asset->delete();
                $audit->record(new AuditData(
                    action: 'media.retention_deleted',
                    auditableType: MediaAsset::class,
                    auditableId: $assetId,
                    actorId: $this->actorId,
                    correlationId: $this->correlationId,
                    metadata: ['object_count' => $asset->variants->count() + 1],
                ));
            }, attempts: 3);
        }
    }
}
