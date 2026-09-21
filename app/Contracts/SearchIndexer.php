<?php

declare(strict_types=1);

namespace App\Contracts;

interface SearchIndexer
{
    /** @param array<string, mixed> $document */
    public function upsert(string $type, string $id, string $locale, array $document): void;

    public function delete(string $type, string $id, string $locale): void;
}
