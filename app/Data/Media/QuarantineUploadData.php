<?php

declare(strict_types=1);

namespace App\Data\Media;

use Illuminate\Http\UploadedFile;

final readonly class QuarantineUploadData
{
    /**
     * @param  list<string>  $allowedMimeTypes
     */
    public function __construct(
        public UploadedFile $file,
        public string $visibility,
        public array $allowedMimeTypes,
        public ?string $uploadedBy = null,
        public ?string $locale = null,
        public ?string $retentionUntil = null,
        public string $fieldName = 'file',
        public ?string $correlationId = null,
        public ?string $title = null,
        public ?string $altText = null,
    ) {}
}
