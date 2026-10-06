<?php

declare(strict_types=1);

namespace App\Enums\Seo;

enum SeoIssueSeverity: string
{
    case Blocking = 'blocking';
    case Warning = 'warning';
    case Information = 'information';

    public function label(): string
    {
        return match ($this) {
            self::Blocking => 'Blocking',
            self::Warning => 'Warning',
            self::Information => 'Information',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Blocking => 0,
            self::Warning => 1,
            self::Information => 2,
        };
    }
}
