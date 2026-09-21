<?php

declare(strict_types=1);

namespace App\Data\PageComposition;

use App\Enums\PageTemplateType;

final readonly class CompositionViewData
{
    /** @param list<SectionViewData> $sections */
    public function __construct(
        public string $id,
        public string $pageKey,
        public string $locale,
        public PageTemplateType $template,
        public int $version,
        public array $sections,
        public bool $preview = false,
    ) {}
}
