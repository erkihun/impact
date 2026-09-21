<?php

declare(strict_types=1);

namespace App\Enums;

enum RetentionRunMode: string
{
    case DryRun = 'dry_run';
    case Execute = 'execute';
}
