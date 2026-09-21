<?php

declare(strict_types=1);

namespace App\Enums\Settings;

enum SettingCategory: string
{
    case General = 'general';
    case Branding = 'branding';
    case Appearance = 'appearance';
    case Homepage = 'homepage';
    case Localization = 'localization';
    case Security = 'security';
    case Authentication = 'authentication';
    case Notifications = 'notifications';
    case Email = 'email';
    case Integrations = 'integrations';
    case Privacy = 'privacy';
    case Content = 'content';
    case Seo = 'seo';
    case Engagement = 'engagement';
    case Media = 'media';
    case Search = 'search';
    case Performance = 'performance';
    case Maintenance = 'maintenance';
    case Features = 'features';
    case Environment = 'environment';
}
