<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaStatus: string
{
    case Quarantined = 'quarantined';
    case Scanning = 'scanning';
    case Clean = 'clean';
    case Processing = 'processing';
    case Ready = 'ready';
    case Rejected = 'rejected';
    case Deleted = 'deleted';
}
