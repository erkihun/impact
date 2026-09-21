<?php

declare(strict_types=1);

namespace App\Services\Workflow;

use App\Enums\ContentWorkflowState;
use App\Exceptions\InvalidStateTransitionException;

final readonly class ContentWorkflow
{
    /** @return list<ContentWorkflowState> */
    public function allowedDestinations(ContentWorkflowState $from): array
    {
        return match ($from) {
            ContentWorkflowState::Draft => [ContentWorkflowState::InReview],
            ContentWorkflowState::InReview => [
                ContentWorkflowState::ChangesRequested,
                ContentWorkflowState::Approved,
            ],
            ContentWorkflowState::ChangesRequested => [ContentWorkflowState::InReview],
            ContentWorkflowState::Approved => [
                ContentWorkflowState::Scheduled,
                ContentWorkflowState::Published,
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
                ContentWorkflowState::Draft,
                ContentWorkflowState::Published,
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
