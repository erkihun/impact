<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientDisplayMode: string
{
    case Named = 'named';
    case Anonymized = 'anonymized';
    case Confidential = 'confidential';
}
