<?php

declare(strict_types=1);

namespace App\Data\Seo;

use Carbon\CarbonInterface;

final readonly class SitemapEntry
{
    public function __construct(
        public string $loc,
        public ?CarbonInterface $lastModified = null,
    ) {}
}
