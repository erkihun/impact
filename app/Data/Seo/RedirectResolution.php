<?php

declare(strict_types=1);

namespace App\Data\Seo;

final readonly class RedirectResolution
{
    /** A null destination with status 410 means "Gone". */
    public function __construct(
        public ?string $destination,
        public int $status,
        public ?string $redirectId = null,
    ) {}

    public function isGone(): bool
    {
        return $this->status === 410;
    }
}
