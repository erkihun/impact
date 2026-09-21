<?php

declare(strict_types=1);

namespace App\Enums;

enum VisibilityRule: string
{
    case Always = 'always';
    case Scheduled = 'scheduled';
    case WhenPopulated = 'when_populated';
}
