<?php

declare(strict_types=1);

use App\Enums\ContentWorkflowState;
use App\Exceptions\InvalidStateTransitionException;
use App\Services\Workflow\ContentWorkflow;

it('allows the documented editorial path', function (): void {
    $workflow = new ContentWorkflow;

    $workflow->assertCanTransition(ContentWorkflowState::Draft, ContentWorkflowState::InReview);
    $workflow->assertCanTransition(ContentWorkflowState::InReview, ContentWorkflowState::Approved);
    $workflow->assertCanTransition(ContentWorkflowState::Approved, ContentWorkflowState::Published);

    expect(true)->toBeTrue();
});

it('allows publishing or scheduling without a review step', function (ContentWorkflowState $from): void {
    $workflow = new ContentWorkflow;

    $workflow->assertCanTransition($from, ContentWorkflowState::Published);
    $workflow->assertCanTransition($from, ContentWorkflowState::Scheduled);

    expect($workflow->allowedDestinations($from)[0])->toBe(ContentWorkflowState::Published);
})->with([
    'draft' => ContentWorkflowState::Draft,
    'in review' => ContentWorkflowState::InReview,
    'changes requested' => ContentWorkflowState::ChangesRequested,
]);

it('rejects leaving the archived state', function (): void {
    (new ContentWorkflow)->assertCanTransition(
        ContentWorkflowState::Archived,
        ContentWorkflowState::Published,
    );
})->throws(InvalidStateTransitionException::class);

it('rejects approving content that is already live', function (): void {
    (new ContentWorkflow)->assertCanTransition(
        ContentWorkflowState::Published,
        ContentWorkflowState::Approved,
    );
})->throws(InvalidStateTransitionException::class);
