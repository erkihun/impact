<?php

declare(strict_types=1);

namespace App\Enums;

enum TranslationStatus: string
{
    case Missing = 'missing';
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Published = 'published';
    case Outdated = 'outdated';
}
