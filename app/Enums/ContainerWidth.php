<?php

declare(strict_types=1);

namespace App\Enums;

enum ContainerWidth: string
{
    case Reading = 'reading';
    case Standard = 'standard';
    case Wide = 'wide';
    case FullBleedMedia = 'full_bleed_media';
}
