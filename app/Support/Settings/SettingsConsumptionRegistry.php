<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Actions\Privacy\ExecuteRetentionAction;
use App\Actions\Workflow\TransitionContentWorkflowAction;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\SystemStatusController;
use App\Http\Middleware\SetLocale;
use App\Support\SettingCatalog;

final class SettingsConsumptionRegistry
{
    /** @return array{consumer: string, file: string, behavior: string}|null */
    public static function for(string $key): ?array
    {
        if (isset(SettingCatalog::ENVIRONMENT_OVERRIDES[$key])) {
            return [
                'consumer' => 'Laravel configuration',
                'file' => 'config/*.php',
                'behavior' => 'Environment value is authoritative and read-only in the CMS.',
            ];
        }

        if (in_array($key, SettingCatalog::INACTIVE_KEYS, true)) {
            return null;
        }

        $category = SettingCatalog::DEFINITIONS[$key]['category'] ?? null;

        return match ($category) {
            'general', 'branding', 'appearance' => [
                'consumer' => PublicUiSettings::class,
                'file' => 'resources/views/layouts/public.blade.php',
                'behavior' => 'Changes public, administration, and authentication identity or semantic design tokens.',
            ],
            'homepage' => [
                'consumer' => HomepageHeroSettings::class,
                'file' => 'resources/views/public/sections/homepage-slider.blade.php',
                'behavior' => 'Controls localized hero slide content, ordering, images, approved actions, and rotation.',
            ],
            'localization' => [
                'consumer' => SetLocale::class,
                'file' => 'app/Http/Middleware/SetLocale.php',
                'behavior' => 'Controls enabled locale routing, fallback, and publication locale requirements.',
            ],
            'security', 'authentication' => [
                'consumer' => PasswordPolicy::class,
                'file' => 'app/Http/Middleware/EnsureSessionIsCurrent.php',
                'behavior' => 'Controls password validation, throttling, MFA freshness, recovery codes, and session lifetime.',
            ],
            'notifications', 'email' => [
                'consumer' => NotificationDeliverySettings::class,
                'file' => 'app/Support/Settings/NotificationDeliverySettings.php',
                'behavior' => 'Controls channel suppression, sender, reply-to, queue, retry attempts, and message footer.',
            ],
            'privacy' => [
                'consumer' => ExecuteRetentionAction::class,
                'file' => 'resources/views/components/ui/consent-banner.blade.php',
                'behavior' => 'Controls consent presentation, policy version validation, and future retention eligibility.',
            ],
            'content' => [
                'consumer' => TransitionContentWorkflowAction::class,
                'file' => 'app/Http/Controllers/Admin/ContentController.php',
                'behavior' => 'Controls slugs, publication reason/default locale, previews, stale review, and pagination.',
            ],
            'seo' => [
                'consumer' => SitemapController::class,
                'file' => 'resources/views/layouts/public.blade.php',
                'behavior' => 'Controls rendered metadata, social cards, robots output, and sitemap availability.',
            ],
            'engagement' => [
                'consumer' => EngagementSettings::class,
                'file' => 'app/Http/Requests/Public/SubmitConsultationRequest.php',
                'behavior' => 'Controls route availability, form validation, references, deduplication, and acknowledgements.',
            ],
            'media' => [
                'consumer' => MediaSettings::class,
                'file' => 'app/Actions/Media/QuarantineUploadAction.php',
                'behavior' => 'Controls effective upload allow-list, lower size limit, alt text, and scanning.',
            ],
            'search' => [
                'consumer' => SearchSettings::class,
                'file' => 'app/Http/Controllers/Public/SearchController.php',
                'behavior' => 'Controls availability, page size, suggestions, safe query logging, and presentation flag.',
            ],
            'performance' => [
                'consumer' => EffectiveSettings::class,
                'file' => 'app/Support/Settings/EffectiveSettings.php',
                'behavior' => 'Controls settings cache lifetime and bounded pagination.',
            ],
            'maintenance' => [
                'consumer' => SystemStatusController::class,
                'file' => 'resources/views/layouts/public.blade.php',
                'behavior' => 'Controls the public maintenance banner and status information route.',
            ],
            'features' => [
                'consumer' => SearchSettings::class,
                'file' => 'app/Http/Controllers/Public/SearchController.php',
                'behavior' => 'Controls the public search presentation without bypassing route authorization.',
            ],
            default => null,
        };
    }
}
