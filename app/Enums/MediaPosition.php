<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaPosition: string
{
    case None = 'none';
    case Start = 'start';
    case End = 'end';
    case Background = 'background';
    case Top = 'top';
    case Bottom = 'bottom';
}
