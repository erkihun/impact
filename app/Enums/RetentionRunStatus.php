<?php

declare(strict_types=1);

namespace App\Enums;

enum RetentionRunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
}
