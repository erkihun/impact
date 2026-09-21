<?php

declare(strict_types=1);

namespace App\Data\Workflow;

use App\Enums\ContentWorkflowState;

final readonly class TransitionContentData
{
    public function __construct(
        public string $contentVersionId,
        public ContentWorkflowState $to,
        public string $correlationId,
        public ?string $note = null,
        public ?string $publishAt = null,
        public ?string $unpublishAt = null,
    ) {}
}
