<?php

declare(strict_types=1);

namespace App\Enums\Settings;

enum SettingSensitivity: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Sensitive = 'sensitive';
    case SecretReference = 'secret_reference';
}
