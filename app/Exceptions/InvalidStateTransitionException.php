<?php

declare(strict_types=1);

namespace App\Exceptions;

use BackedEnum;
use Exception;

final class InvalidStateTransitionException extends Exception
{
    public function __construct(
        public readonly BackedEnum $from,
        public readonly BackedEnum $to,
    ) {
        parent::__construct("State cannot transition from {$from->value} to {$to->value}.");
    }
}
