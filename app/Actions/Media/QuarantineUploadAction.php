<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Media\QuarantineUploadData;
use App\Enums\MediaStatus;
use App\Jobs\Media\ScanMediaAssetJob;
use App\Models\MediaAsset;
use App\Support\Settings\MediaSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class QuarantineUploadAction
{
    public function __construct(
        private AuditRecorder $audit,
        private MediaSettings $settings,
    ) {}

    public function execute(QuarantineUploadData $data): MediaAsset
    {
        $mimeType = $data->file->getMimeType();
        if ($mimeType === null || ! in_array($mimeType, $data->allowedMimeTypes, true)) {
            throw ValidationException::withMessages([
                $data->fieldName => __('The file signature is not an allowed type.'),
            ]);
        }

        $temporaryPath = $data->file->getRealPath();
        if ($temporaryPath === false) {
            throw ValidationException::withMessages([$data->fieldName => __('The uploaded file is unavailable.')]);
        }

        $disk = (string) config('impact.files.quarantine_disk', 'local');
        $extension = strtolower($data->file->guessExtension() ?: 'bin');
        $objectId = (string) Str::uuid7();
        $path = 'quarantine/'.now('UTC')->format('Y/m/d')."/{$objectId}/{$objectId}.{$extension}";
        $stream = fopen($temporaryPath, 'rb');
        if ($stream === false || ! Storage::disk($disk)->put($path, $stream, ['visibility' => 'private'])) {
            throw ValidationException::withMessages([$data->fieldName => __('The file could not be stored safely.')]);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }

        try {
            $asset = DB::transaction(function () use ($data, $disk, $path, $mimeType, $temporaryPath): MediaAsset {
                $asset = MediaAsset::query()->create([
                    'original_name' => Str::limit($data->file->getClientOriginalName(), 255, ''),
                    'disk' => $disk,
                    'path' => $path,
                    'mime_type' => $mimeType,
                    'size_bytes' => $data->file->getSize(),
                    'sha256' => hash_file('sha256', $temporaryPath),
                    'visibility' => $data->visibility,
                    'scan_status' => MediaStatus::Quarantined,
                    'processing_status' => MediaStatus::Quarantined,
                    'title' => $data->title,
                    'alt_text' => $data->altText,
                    'locale' => $data->locale,
                    'uploaded_by' => $data->uploadedBy,
                    'retention_until' => $data->retentionUntil,
                ]);
                if ($data->uploadedBy !== null && $data->correlationId !== null) {
                    $this->audit->record(new AuditData(
                        action: 'media.quarantined',
                        auditableType: MediaAsset::class,
                        auditableId: $asset->id,
                        actorId: $data->uploadedBy,
                        correlationId: $data->correlationId,
                        afterHash: hash('sha256', $asset->toJson()),
                        metadata: [
                            'mime_type' => $mimeType,
                            'size_bytes' => $asset->size_bytes,
                            'visibility' => $data->visibility,
                        ],
                    ));
                }

                return $asset;
            }, attempts: 3);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }

        if ($this->settings->requiresMalwareScan()) {
            ScanMediaAssetJob::dispatch(
                (string) $asset->id,
                $data->correlationId ?? (string) Str::uuid7(),
            )->afterCommit();
        }

        return $asset;
    }
}
