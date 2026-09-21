<?php

declare(strict_types=1);

namespace App\Actions\PageComposition;

use App\Models\PageComposition;
use App\Models\PageSection;
use App\Models\User;
use App\Services\PageCompositionMutator;

final readonly class DuplicatePageSectionAction
{
    public function __construct(private PageCompositionMutator $mutator) {}

    public function execute(
        User $actor,
        PageComposition $composition,
        PageSection $section,
        int $expectedLockVersion,
        string $correlationId,
    ): PageSection {
        return $this->mutator->duplicate(
            $actor, $composition, $section, $expectedLockVersion, $correlationId,
        );
    }
}
