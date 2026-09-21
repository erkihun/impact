<?php

declare(strict_types=1);

namespace App\Support;

final class RolePermissionMap
{
    /** @var array<string, list<string>> */
    public const MAP = [
        'contributor' => ['content.view', 'content.create', 'content.update', 'pages.view'],
        'editor' => [
            'content.view', 'content.create', 'content.update', 'content.submit',
            'pages.view', 'pages.create', 'pages.update', 'pages.preview',
            'media.view', 'media.create',
        ],
        'translator' => ['content.view', 'translations.view', 'translations.update'],
        'reviewer' => ['content.view', 'content.approve', 'pages.view', 'pages.preview', 'pages.approve'],
        'publisher' => [
            'content.view', 'content.publish', 'content.rollback',
            'pages.view', 'pages.preview', 'pages.publish',
            'media.view', 'media.approve',
        ],
        'engagement_officer' => ['engagement.view', 'engagement.assign', 'engagement.update-status'],
        'recruitment_officer' => ['vacancies.manage', 'applications.view', 'applications.update-status'],
        'administrator' => [
            'users.view', 'users.manage', 'roles.manage', 'settings.manage',
            'pages.view', 'pages.create', 'pages.update', 'pages.preview', 'pages.approve', 'pages.publish', 'pages.delete',
            'navigation.manage',
            'services.manage', 'industries.manage', 'experts.manage', 'case-studies.manage',
            'insights.manage', 'events.manage', 'vacancies.manage', 'media.view',
            'media.create', 'media.approve', 'media.delete', 'search.manage', 'seo.manage', 'translations.view',
            'privacy.retention.execute',
        ],
        'security_auditor' => ['audit.view', 'operations.view'],
    ];
}
