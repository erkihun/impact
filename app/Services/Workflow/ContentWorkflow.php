<?php

declare(strict_types=1);

namespace App\Services\Workflow;

use App\Enums\ContentWorkflowState;
use App\Exceptions\InvalidStateTransitionException;

final readonly class ContentWorkflow
{
    /**
     * Review is optional: anything not yet live can be published or scheduled
     * directly, while teams that want a second pair of eyes can still route
     * work through review and approval.
     *
     * @return list<ContentWorkflowState>
     */
    public function allowedDestinations(ContentWorkflowState $from): array
    {
        return match ($from) {
            ContentWorkflowState::Draft => [
                ContentWorkflowState::Published,
                ContentWorkflowState::Scheduled,
                ContentWorkflowState::InReview,
            ],
            ContentWorkflowState::InReview => [
                ContentWorkflowState::Published,
                ContentWorkflowState::Scheduled,
                ContentWorkflowState::Approved,
                ContentWorkflowState::ChangesRequested,
            ],
            ContentWorkflowState::ChangesRequested => [
                ContentWorkflowState::Published,
                ContentWorkflowState::Scheduled,
                ContentWorkflowState::InReview,
            ],
            ContentWorkflowState::Approved => [
                ContentWorkflowState::Published,
                ContentWorkflowState::Scheduled,
            ],
            ContentWorkflowState::Scheduled => [
                ContentWorkflowState::Published,
                ContentWorkflowState::ChangesRequested,
            ],
            ContentWorkflowState::Published => [
                ContentWorkflowState::Unpublished,
                ContentWorkflowState::Archived,
            ],
            ContentWorkflowState::Unpublished => [
                ContentWorkflowState::Published,
                ContentWorkflowState::Draft,
                ContentWorkflowState::Archived,
            ],
            ContentWorkflowState::Archived => [],
        };
    }

    public function assertCanTransition(
        ContentWorkflowState $from,
        ContentWorkflowState $to,
    ): void {
        if (! in_array($to, $this->allowedDestinations($from), true)) {
            throw new InvalidStateTransitionException($from, $to);
        }
    }
}
