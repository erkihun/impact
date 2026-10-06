<?php

declare(strict_types=1);

namespace App\Data\Seo;

final readonly class SeoImage
{
    public function __construct(
        public string $url,
        public string $alt,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $mimeType = null,
    ) {}

    /** @return array{url: string, alt: string, width: int|null, height: int|null} */
    public function toArray(): array
    {
        return ['url' => $this->url, 'alt' => $this->alt, 'width' => $this->width, 'height' => $this->height];
    }
}
