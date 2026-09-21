<?php

declare(strict_types=1);

namespace App\Enums;

enum VacancyStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
    case Archived = 'archived';
}
