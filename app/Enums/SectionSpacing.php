<?php

declare(strict_types=1);

namespace App\Enums;

enum SectionSpacing: string
{
    case Compact = 'compact';
    case Standard = 'standard';
    case Spacious = 'spacious';
    case Immersive = 'immersive';
}
