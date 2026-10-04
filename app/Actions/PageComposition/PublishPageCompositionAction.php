<?php

declare(strict_types=1);

namespace App\Actions\PageComposition;

use App\Enums\PageCompositionState;
use App\Exceptions\ContentVersionConflictException;
use Illuminate\Support\Facades\DB;
use App\Models\PageComposition;
use App\Models\User;

final readonly class PublishPageCompositionAction
{
    public function __construct(private TransitionPageCompositionAction $transition) {}

    public function execute(
        User $actor,
        PageComposition $composition,
        string $correlationId,
        int $expectedLockVersion,
        ?string $comment = null,
    ): PageComposition {
        abort_unless($actor->hasPermission('pages.publish'), 403);

        return DB::transaction(function () use ($actor, $composition, $correlationId, $expectedLockVersion, $comment): PageComposition {
            $locked = PageComposition::query()->lockForUpdate()->findOrFail($composition->getKey());
            if ($locked->lock_version !== $expectedLockVersion) {
                throw new ContentVersionConflictException((string) $expectedLockVersion, (string) $locked->lock_version);
            }

            $steps = match ($locked->state) {
                PageCompositionState::Draft, PageCompositionState::ChangesRequested => [PageCompositionState::InReview, PageCompositionState::Approved, PageCompositionState::Published],
                PageCompositionState::InReview => [PageCompositionState::Approved, PageCompositionState::Published],
                default => [PageCompositionState::Published],
            };
            foreach ($steps as $step) {
                $locked = $this->transition->execute($actor, $locked, $step, $correlationId, $comment);
            }

            return $locked;
        });
    }
}
