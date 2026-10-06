<?php

declare(strict_types=1);

namespace App\Enums\Seo;

enum RedirectOrigin: string
{
    case Manual = 'manual';
    case SlugChange = 'slug_change';
    case LegacyLocale = 'legacy_locale';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::SlugChange => 'Published slug change',
            self::LegacyLocale => 'Legacy language URL',
            self::Retired => 'Deleted published content',
        };
    }
}
