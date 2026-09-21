<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ContentWorkflowState;
use App\Models\ContentItem;
use App\Models\User;

final readonly class ContentItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('content.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('content.create');
    }

    public function view(User $user, ContentItem $content): bool
    {
        return $user->hasPermission('content.view');
    }

    public function update(User $user, ContentItem $content): bool
    {
        return $user->hasPermission('content.update')
            && in_array((string) $content->getRawOriginal('status'), [
                ContentWorkflowState::Draft->value,
                ContentWorkflowState::ChangesRequested->value,
                ContentWorkflowState::Unpublished->value,
            ], true);
    }

    public function transition(
        User $user,
        ContentItem $content,
        ContentWorkflowState $to,
    ): bool {
        $permission = match ($to) {
            ContentWorkflowState::InReview => 'content.submit',
            ContentWorkflowState::ChangesRequested,
            ContentWorkflowState::Approved => 'content.approve',
            ContentWorkflowState::Scheduled,
            ContentWorkflowState::Published,
            ContentWorkflowState::Unpublished,
            ContentWorkflowState::Archived => 'content.publish',
            ContentWorkflowState::Draft => 'content.rollback',
        };

        if (! $user->hasPermission($permission)) {
            return false;
        }

        return ! (
            config('impact.workflow.prevent_self_approval', true)
            && $to === ContentWorkflowState::Approved
            && (
                (string) $content->owner_id === (string) $user->getKey()
                || $content->currentVersion()
                    ->where('created_by', $user->getKey())
                    ->exists()
            )
        );
    }

    public function rollback(User $user, ContentItem $content): bool
    {
        return $user->hasPermission('content.rollback');
    }
}
