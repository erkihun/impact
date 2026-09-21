<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentWorkflowState: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Unpublished = 'unpublished';
    case Archived = 'archived';
}
