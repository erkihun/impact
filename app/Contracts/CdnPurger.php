<?php

declare(strict_types=1);

namespace App\Contracts;

interface CdnPurger
{
    /** @param  list<string>  $urls  absolute canonical URLs */
    public function purge(array $urls): void;
}
