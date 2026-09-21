<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Restricted = 'restricted';
    case Quarantine = 'quarantine';
}
