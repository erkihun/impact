<?php

declare(strict_types=1);

namespace App\Data\Engagement;

use App\Enums\SubmissionStatus;

final readonly class UpdateSubmissionData
{
    public function __construct(
        public string $submissionId,
        public string $actorId,
        public SubmissionStatus $status,
        public string $correlationId,
        public ?string $assignedTo = null,
        public ?string $note = null,
    ) {}
}
