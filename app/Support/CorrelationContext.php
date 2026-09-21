<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class CorrelationContext
{
    private string $id;

    public function __construct(?string $id = null)
    {
        $this->id = $id ?? (string) Str::uuid7();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function replace(string $id): void
    {
        $this->id = $id;
    }

    public function childId(): string
    {
        return sprintf('%s.%s', $this->id, Str::lower(Str::random(8)));
    }
}
