<?php

declare(strict_types=1);

namespace App\Data\Settings;

use Carbon\CarbonImmutable;

final readonly class EffectiveSettingValue
{
    /**
     * @param  bool|int|float|string|array<mixed>|null  $value
     */
    public function __construct(
        public string $key,
        public bool|int|float|string|array|null $value,
        public string $source,
        public bool $isOverridden,
        public bool $isEditable,
        public bool $requiresRestart,
        public bool $requiresDeployment,
        public ?CarbonImmutable $updatedAt,
        public bool $isValid = true,
        public ?string $warning = null,
    ) {}
}
