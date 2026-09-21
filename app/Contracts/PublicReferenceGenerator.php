<?php

declare(strict_types=1);

namespace App\Contracts;

interface PublicReferenceGenerator
{
    public function generate(string $prefix): string;
}
