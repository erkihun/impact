<?php

declare(strict_types=1);

namespace App\Data\PageComposition;

use App\Enums\PageSectionType;

final readonly class SectionViewData
{
    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, string>  $presentation
     * @param  list<mixed>  $relations
     * @param  list<mixed>  $media
     * @param  list<mixed>  $actions
     */
    public function __construct(
        public string $id,
        public string $stableKey,
        public PageSectionType $type,
        public string $variant,
        public string $renderer,
        public array $content,
        public array $presentation,
        public array $relations,
        public array $media,
        public array $actions,
    ) {}
}
