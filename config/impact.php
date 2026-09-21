<?php

declare(strict_types=1);

return [
    'security' => [
        'trusted_hosts' => array_values(array_map(
            static fn (string $host): string => '^'.preg_quote(trim($host), '/').'$',
            array_filter(
                explode(',', (string) env('TRUSTED_HOSTS', '')),
                static fn (string $host): bool => trim($host) !== '',
            ),
        )),
        'trusted_proxies' => env('TRUSTED_PROXIES'),
        'privileged_mfa_required' => env('PRIVILEGED_MFA_REQUIRED', true),
        'recent_mfa_required' => env('RECENT_MFA_REQUIRED', true),
    ],
    'locales' => [
        'default' => env('IMPACT_DEFAULT_LOCALE', 'en'),
        'supported' => ['en', 'am'],
    ],
    'privacy' => [
        'policy_version' => env('PRIVACY_POLICY_VERSION', '2026-07-26'),
        'search_query_log_days' => (int) env('SEARCH_QUERY_LOG_DAYS', 90),
        'analytics_outbox_days' => (int) env('ANALYTICS_OUTBOX_DAYS', 30),
    ],
    'retention' => [
        'policy_version' => env('RETENTION_POLICY_VERSION', '2026-07-26'),
        'engagement_days' => (int) env('ENGAGEMENT_RETENTION_DAYS', 730),
        'application_days' => (int) env('APPLICATION_RETENTION_DAYS', 730),
        'event_registration_days' => (int) env('EVENT_REGISTRATION_RETENTION_DAYS', 365),
        'execution_enabled' => env('RETENTION_EXECUTION_ENABLED', false),
        'approved_by' => env('RETENTION_APPROVED_BY'),
    ],
    'newsletter' => [
        'confirmation_ttl_hours' => (int) env('NEWSLETTER_CONFIRMATION_TTL_HOURS', 48),
    ],
    'workflow' => [
        'prevent_self_approval' => env('WORKFLOW_PREVENT_SELF_APPROVAL', true),
    ],
    'development_admin' => [
        'email' => env('DEVELOPMENT_ADMIN_EMAIL', 'impact@local.com'),
        'password' => env('DEVELOPMENT_ADMIN_PASSWORD', 'Impact2018'),
    ],
    'files' => [
        'quarantine_disk' => env('QUARANTINE_DISK', 'local'),
        'public_disk' => env('PUBLIC_MEDIA_DISK', 'public'),
        'private_disk' => env('PRIVATE_MEDIA_DISK', 'local'),
        'engagement_attachment_max_files' => (int) env('ENGAGEMENT_ATTACHMENT_MAX_FILES', 5),
        'engagement_attachment_max_kilobytes' => (int) env('ENGAGEMENT_ATTACHMENT_MAX_KILOBYTES', 20480),
        'engagement_allowed_extensions' => ['pdf', 'docx', 'xlsx', 'pptx'],
        'engagement_allowed_mime_types' => [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ],
        'media_image_max_kilobytes' => (int) env('MEDIA_IMAGE_MAX_KILOBYTES', 5120),
        'malware_scanning_enabled' => env('MALWARE_SCANNING_ENABLED', false),
        'clamav_binary' => env('CLAMAV_BINARY', 'clamdscan'),
        'scan_timeout_seconds' => (int) env('MALWARE_SCAN_TIMEOUT_SECONDS', 120),
    ],
];
