<?php

declare(strict_types=1);

namespace App\Enums;

enum SurfaceTone: string
{
    case Default = 'default';
    case Quiet = 'quiet';
    case Brand = 'brand';
    case Information = 'information';
    case AccentSubtle = 'accent_subtle';
    case Dark = 'dark';
}
