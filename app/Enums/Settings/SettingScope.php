<?php

declare(strict_types=1);

namespace App\Enums\Settings;

enum SettingScope: string
{
    case Global = 'global';
    case Environment = 'environment';
    case Locale = 'locale';
}
