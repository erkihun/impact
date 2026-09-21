<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaStatus;
use App\Enums\MediaVisibility;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property MediaVisibility $visibility
 * @property MediaStatus $scan_status
 * @property MediaStatus $processing_status
 * @property string|null $alt_text
 */
final class MediaAsset extends BaseModel
{
    protected function casts(): array
    {
        return [
            'visibility' => MediaVisibility::class,
            'scan_status' => MediaStatus::class,
            'processing_status' => MediaStatus::class,
            'retention_until' => 'immutable_date',
        ];
    }

    /** @return HasMany<MediaVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(MediaVariant::class);
    }

    /** @return HasMany<ApplicationFile, $this> */
    public function applicationFiles(): HasMany
    {
        return $this->hasMany(ApplicationFile::class);
    }

    /** @return HasMany<SubmissionFile, $this> */
    public function submissionFiles(): HasMany
    {
        return $this->hasMany(SubmissionFile::class);
    }

    public function isPubliclyUsable(): bool
    {
        return $this->getRawOriginal('visibility') === MediaVisibility::Public->value
            && $this->getRawOriginal('scan_status') === MediaStatus::Clean->value
            && $this->getRawOriginal('processing_status') === MediaStatus::Ready->value;
    }

    public function publicUrl(): string
    {
        $disk = (string) $this->disk;

        if ($disk === (string) config('impact.files.public_disk', 'public')
            && config("filesystems.disks.{$disk}.driver") === 'local') {
            return url('/storage/'.ltrim($this->path, '/'));
        }

        return Storage::disk($disk)->url($this->path);
    }
}
