<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsletterStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Unsubscribed = 'unsubscribed';
    case Suppressed = 'suppressed';
}
