<?php

declare(strict_types=1);

namespace App\Support;

final class PermissionCatalog
{
    /** @var array<string, string> */
    public const ALL = [
        'content.view' => 'View drafts and versions',
        'content.create' => 'Create content records',
        'content.update' => 'Edit permitted drafts',
        'content.submit' => 'Submit content for review',
        'content.approve' => 'Approve or request changes',
        'content.publish' => 'Publish or schedule approved versions',
        'content.rollback' => 'Rollback to an approved historical version',
        'pages.view' => 'View page compositions and section versions',
        'pages.create' => 'Create page compositions',
        'pages.update' => 'Edit draft page compositions',
        'pages.preview' => 'Preview non-public page compositions',
        'pages.approve' => 'Approve or request changes to page compositions',
        'pages.publish' => 'Publish approved page compositions',
        'pages.delete' => 'Archive page compositions',
        'navigation.manage' => 'Manage public navigation and footer links',
        'services.manage' => 'Manage services and relationships',
        'industries.manage' => 'Manage industries and relationships',
        'experts.manage' => 'Manage expert profiles and credentials',
        'case-studies.manage' => 'Manage case studies and testimonials',
        'insights.manage' => 'Manage insights and publications',
        'events.manage' => 'Manage events',
        'events.registrations.view' => 'View event registrants',
        'vacancies.manage' => 'Manage vacancies',
        'applications.view' => 'View applications',
        'applications.update-status' => 'Change application status',
        'engagement.view' => 'View engagement submissions',
        'engagement.assign' => 'Assign submissions',
        'engagement.update-status' => 'Update submission lifecycle',
        'media.view' => 'View media',
        'media.create' => 'Upload media',
        'media.approve' => 'Approve and promote clean processed media',
        'media.delete' => 'Delete unreferenced media',
        'translations.view' => 'View translation queue',
        'translations.update' => 'Edit localized versions',
        'search.manage' => 'Manage search synonyms and index',
        'seo.manage' => 'Manage SEO metadata and redirects',
        'users.view' => 'View users',
        'users.manage' => 'Create and update users',
        'roles.manage' => 'Manage roles and permissions',
        'audit.view' => 'View and export audit records',
        'settings.manage' => 'Manage system settings',
        'operations.view' => 'View runtime health and operational evidence',
        'privacy.retention.execute' => 'Approve and execute retention anonymization',
    ];
}
