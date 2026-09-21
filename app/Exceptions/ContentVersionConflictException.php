<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class ContentVersionConflictException extends Exception
{
    public function __construct(
        public readonly string $expectedVersionId,
        public readonly string $currentVersionId,
    ) {
        parent::__construct('The content was updated by another editor.');
    }
}
