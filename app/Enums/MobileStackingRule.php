<?php

declare(strict_types=1);

namespace App\Enums;

enum MobileStackingRule: string
{
    case ContentFirst = 'content_first';
    case MediaFirst = 'media_first';
    case Preserve = 'preserve';
}
