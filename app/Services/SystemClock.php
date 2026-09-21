<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Clock;
use Carbon\CarbonImmutable;

final readonly class SystemClock implements Clock
{
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }

    public function today(): CarbonImmutable
    {
        return $this->now()->startOfDay();
    }
}
