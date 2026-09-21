<?php

declare(strict_types=1);

namespace App\Enums;

enum PageCompositionState: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function editable(): bool
    {
        return in_array($this, [self::Draft, self::ChangesRequested], true);
    }
}
