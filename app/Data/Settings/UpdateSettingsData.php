<?php

declare(strict_types=1);

namespace App\Data\Settings;

final readonly class UpdateSettingsData
{
    /** @param array<string, bool|int|string|null> $values */
    public function __construct(
        public array $values,
        public string $category,
        public string $actorId,
        public string $correlationId,
        public string $expectedVersion,
        public ?string $changeReason,
        public bool $reset = false,
    ) {}
}
