<?php

declare(strict_types=1);

namespace App\Enums;

enum CardVariant: string
{
    case Editorial = 'editorial';
    case Bordered = 'bordered';
    case Quiet = 'quiet';
    case Immersive = 'immersive';
}
