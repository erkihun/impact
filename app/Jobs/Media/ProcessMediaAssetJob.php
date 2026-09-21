<?php

declare(strict_types=1);

namespace App\Jobs\Media;

use App\Enums\MediaStatus;
use App\Models\MediaAsset;
use App\Models\MediaVariant;
use App\Services\Media\ImageVariantGenerator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class ProcessMediaAssetJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public function __construct(
        public readonly string $mediaAssetId,
        public readonly string $correlationId,
    ) {
        $this->onQueue('media');
    }

    public function uniqueId(): string
    {
        return $this->mediaAssetId;
    }

    public function handle(ImageVariantGenerator $variants): void
    {
        $asset = MediaAsset::query()->findOrFail($this->mediaAssetId);
        if ($asset->getRawOriginal('scan_status') !== MediaStatus::Clean->value
            || $asset->getRawOriginal('processing_status') === MediaStatus::Ready->value) {
            return;
        }

        $asset->update(['processing_status' => MediaStatus::Processing]);
        $generated = str_starts_with($asset->mime_type, 'image/')
            ? $variants->generate($asset)
            : [];

        DB::transaction(function () use ($asset, $generated): void {
            foreach ($generated as $variant) {
                MediaVariant::query()->updateOrCreate(
                    ['media_asset_id' => $asset->id, 'variant' => $variant['variant']],
                    $variant,
                );
            }
            $asset->update(['processing_status' => MediaStatus::Ready]);
        }, attempts: 3);
    }
}
