<?php

declare(strict_types=1);

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case RegistrationOpen = 'registration_open';
    case RegistrationClosed = 'registration_closed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
