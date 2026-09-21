<?php

declare(strict_types=1);

namespace App\Actions\PageComposition;

use App\Data\PageComposition\UpsertSectionData;
use App\Models\PageComposition;
use App\Models\PageSection;
use App\Models\User;
use App\Services\PageCompositionMutator;

final readonly class UpdatePageSectionAction
{
    public function __construct(private PageCompositionMutator $mutator) {}

    public function execute(
        User $actor,
        PageComposition $composition,
        PageSection $section,
        UpsertSectionData $data,
    ): PageSection {
        return $this->mutator->update($actor, $composition, $section, $data);
    }
}
