<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Media\ApproveMediaData;
use App\Enums\MediaStatus;
use App\Enums\MediaVisibility;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class ApproveMediaAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(User $actor, ApproveMediaData $data): MediaAsset
    {
        if (! $actor->hasPermission('media.approve')) {
            throw new AuthorizationException;
        }

        $asset = MediaAsset::query()->with('variants')->findOrFail($data->mediaAssetId);
        if ($asset->applicationFiles()->exists() || $asset->submissionFiles()->exists()) {
            throw new AuthorizationException;
        }
        if ($asset->getRawOriginal('scan_status') !== MediaStatus::Clean->value
            || $asset->getRawOriginal('processing_status') !== MediaStatus::Ready->value) {
            throw ValidationException::withMessages([
                'media' => __('Only clean, fully processed media can be approved.'),
            ]);
        }

        $sourceDisk = $asset->disk;
        $targetDisk = $asset->getRawOriginal('visibility') === MediaVisibility::Public->value
            ? (string) config('impact.files.public_disk', 'public')
            : (string) config('impact.files.private_disk', 'local');
        $extension = pathinfo($asset->path, PATHINFO_EXTENSION);
        $targetPath = 'media/'.now('UTC')->format('Y/m').'/'.$asset->id.'/'.$asset->id.'.'.$extension;
        $copied = [];

        try {
            $this->copy($sourceDisk, $asset->path, $targetDisk, $targetPath);
            $copied[] = $targetPath;
            $variantPaths = [];
            foreach ($asset->variants as $variant) {
                $variantTarget = 'media/'.now('UTC')->format('Y/m').'/'.$asset->id
                    .'/variants/'.$variant->variant.'.webp';
                $this->copy($sourceDisk, $variant->path, $targetDisk, $variantTarget);
                $copied[] = $variantTarget;
                $variantPaths[$variant->id] = $variantTarget;
            }

            DB::transaction(function () use (
                $asset,
                $data,
                $sourceDisk,
                $targetDisk,
                $targetPath,
                $variantPaths,
            ): void {
                $locked = MediaAsset::query()->lockForUpdate()->findOrFail($asset->id);
                if ($locked->getRawOriginal('scan_status') !== MediaStatus::Clean->value
                    || $locked->getRawOriginal('processing_status') !== MediaStatus::Ready->value) {
                    throw ValidationException::withMessages([
                        'media' => __('Media state changed before approval.'),
                    ]);
                }
                $beforeHash = hash('sha256', $locked->toJson());
                $locked->update(['disk' => $targetDisk, 'path' => $targetPath]);
                foreach ($variantPaths as $variantId => $path) {
                    $locked->variants()->whereKey($variantId)->update(['path' => $path]);
                }
                $this->audit->record(new AuditData(
                    action: 'media.approved',
                    auditableType: MediaAsset::class,
                    auditableId: $locked->id,
                    actorId: $data->actorId,
                    correlationId: $data->correlationId,
                    beforeHash: $beforeHash,
                    afterHash: hash('sha256', $locked->fresh()->toJson()),
                    metadata: ['source_disk' => $sourceDisk, 'target_disk' => $targetDisk],
                ));
            }, attempts: 3);
        } catch (Throwable $exception) {
            Storage::disk($targetDisk)->delete($copied);
            throw $exception;
        }

        Storage::disk($sourceDisk)->delete([
            $asset->path,
            ...$asset->variants->pluck('path')->all(),
        ]);

        return $asset->refresh()->load('variants');
    }

    private function copy(string $sourceDisk, string $sourcePath, string $targetDisk, string $targetPath): void
    {
        $stream = Storage::disk($sourceDisk)->readStream($sourcePath);
        if ($stream === null || ! Storage::disk($targetDisk)->put(
            $targetPath,
            $stream,
            ['visibility' => $targetDisk === (string) config('impact.files.public_disk', 'public') ? 'public' : 'private'],
        )) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw ValidationException::withMessages(['media' => __('Media promotion failed safely.')]);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
    }
}
