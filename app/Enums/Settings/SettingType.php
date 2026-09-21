<?php

declare(strict_types=1);

namespace App\Enums\Settings;

enum SettingType: string
{
    case Boolean = 'boolean';
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Email = 'email';
    case Url = 'url';
    case Phone = 'phone';
    case Color = 'color';
    case Enum = 'enum';
    case MultiSelect = 'multi_select';
    case LocaleList = 'locale_list';
    case Duration = 'duration';
    case MediaReference = 'media_reference';
    case SecretReference = 'secret_reference';
}
