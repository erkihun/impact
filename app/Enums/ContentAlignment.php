<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentAlignment: string
{
    case Start = 'start';
    case Center = 'center';
    case End = 'end';
    case Split = 'split';
}
