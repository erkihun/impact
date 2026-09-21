<?php

declare(strict_types=1);

namespace App\Enums;

enum ConsentCategory: string
{
    case Necessary = 'necessary';
    case Preferences = 'preferences';
    case Analytics = 'analytics';
    case Marketing = 'marketing';
}
