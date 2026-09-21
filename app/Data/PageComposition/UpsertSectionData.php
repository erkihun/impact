<?php

declare(strict_types=1);

namespace App\Data\PageComposition;

use App\Enums\ContentSelectionMode;
use App\Enums\PageSectionType;
use App\Enums\VisibilityRule;

final readonly class UpsertSectionData
{
    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, string>  $presentation
     */
    public function __construct(
        public PageSectionType $type,
        public string $variant,
        public string $editorLabel,
        public array $content,
        public array $presentation,
        public bool $enabled,
        public VisibilityRule $visibilityRule,
        public ?string $visibleFrom,
        public ?string $visibleUntil,
        public ?ContentSelectionMode $selectionMode,
        public ?int $maximumItems,
        public int $expectedLockVersion,
        public string $correlationId,
        /** @var list<string> */
        public array $mediaAssetIds = [],
        public bool $replaceMedia = false,
    ) {}
}
