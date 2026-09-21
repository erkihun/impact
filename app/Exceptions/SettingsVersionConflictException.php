<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class SettingsVersionConflictException extends RuntimeException
{
    public function __construct(public readonly string $category)
    {
        parent::__construct('The settings group changed after it was opened.');
    }
}
