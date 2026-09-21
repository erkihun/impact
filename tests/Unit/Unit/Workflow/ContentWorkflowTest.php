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

it('rejects publishing a draft directly', function (): void {
    (new ContentWorkflow)->assertCanTransition(
        ContentWorkflowState::Draft,
        ContentWorkflowState::Published,
    );
})->throws(InvalidStateTransitionException::class);
