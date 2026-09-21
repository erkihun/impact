<?php

declare(strict_types=1);

namespace App\Actions\PageComposition;

use App\Models\PageComposition;
use App\Models\User;
use App\Services\PageCompositionMutator;

final readonly class ReorderPageSectionsAction
{
    public function __construct(private PageCompositionMutator $mutator) {}

    /** @param list<string> $sectionIds */
    public function execute(
        User $actor,
        PageComposition $composition,
        array $sectionIds,
        int $expectedLockVersion,
        string $correlationId,
    ): PageComposition {
        return $this->mutator->reorder(
            $actor, $composition, $sectionIds, $expectedLockVersion, $correlationId,
        );
    }
}
