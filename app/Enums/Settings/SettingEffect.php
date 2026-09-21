<?php

declare(strict_types=1);

namespace App\Enums\Settings;

enum SettingEffect: string
{
    case Immediate = 'immediate';
    case ClearsCache = 'clears_cache';
    case AffectsPublicWebsite = 'affects_public_website';
    case AffectsNotifications = 'affects_notifications';
    case AffectsSecurity = 'affects_security';
    case AffectsSessions = 'affects_sessions';
    case RequiresQueueRestart = 'requires_queue_restart';
    case RequiresSchedulerRestart = 'requires_scheduler_restart';
    case RequiresApplicationRestart = 'requires_application_restart';
    case RequiresDeployment = 'requires_deployment';
}
