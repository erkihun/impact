<?php

declare(strict_types=1);

namespace App\Actions\PageComposition;

use App\Enums\PageCompositionState;
use App\Models\PageComposition;
use App\Models\User;

final readonly class PublishPageCompositionAction
{
    public function __construct(private TransitionPageCompositionAction $transition) {}

    public function execute(
        User $actor,
        PageComposition $composition,
        string $correlationId,
        ?string $comment = null,
    ): PageComposition {
        return $this->transition->execute(
            $actor,
            $composition,
            PageCompositionState::Published,
            $correlationId,
            $comment,
        );
    }
}
