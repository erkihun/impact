<?php

declare(strict_types=1);

namespace App\Jobs\Media;

use App\Contracts\MalwareScanner;
use App\Enums\MediaStatus;
use App\Models\MediaAsset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ScanMediaAssetJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public int $timeout = 180;

    public function __construct(
        public readonly string $mediaAssetId,
        public readonly string $correlationId,
    ) {
        $this->onQueue('media');
    }

    public function handle(MalwareScanner $scanner): void
    {
        $asset = MediaAsset::query()->findOrFail($this->mediaAssetId);
        if (! in_array((string) $asset->getRawOriginal('scan_status'), [
            MediaStatus::Quarantined->value,
            MediaStatus::Scanning->value,
        ], true)) {
            return;
        }

        $asset->update(['scan_status' => MediaStatus::Scanning]);
        $result = $scanner->scan($asset->disk, $asset->path);
        $asset->update([
            'scan_status' => $result['clean'] ? MediaStatus::Clean : MediaStatus::Rejected,
            'processing_status' => $result['clean'] ? MediaStatus::Processing : MediaStatus::Rejected,
        ]);
        if ($result['clean']) {
            ProcessMediaAssetJob::dispatch($asset->id, $this->correlationId);
        }
    }
}
