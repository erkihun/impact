<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Settings\SettingCategory;
use App\Enums\Settings\SettingEffect;
use App\Enums\Settings\SettingScope;
use App\Enums\Settings\SettingSensitivity;
use App\Enums\Settings\SettingType;
use Illuminate\Support\Collection;

final class SettingCatalog
{
    /** @var array<string, array{label: string, description: string, group: string, navigation: string, icon: string}> */
    public const CATEGORIES = [
        'general' => ['label' => 'General', 'description' => 'Core organization identity, contact, and operating defaults.', 'group' => 'Organization', 'navigation' => 'Organization', 'icon' => 'organization'],
        'branding' => ['label' => 'Branding and Identity', 'description' => 'Approved visual identity, logos, colors, and brand previews.', 'group' => 'Organization', 'navigation' => 'Organization', 'icon' => 'branding'],
        'appearance' => ['label' => 'Appearance', 'description' => 'Interface density, shell behavior, and approved visual preferences.', 'group' => 'Experience', 'navigation' => 'Experience', 'icon' => 'appearance'],
        'homepage' => ['label' => 'Homepage Hero', 'description' => 'Bilingual hero slides, images, ordering, actions, and rotation behavior.', 'group' => 'Experience', 'navigation' => 'Experience', 'icon' => 'homepage'],
        'localization' => ['label' => 'Localization', 'description' => 'Language, date, time, number, and route behavior.', 'group' => 'Experience', 'navigation' => 'Experience', 'icon' => 'localization'],
        'security' => ['label' => 'Security', 'description' => 'Application-controlled security policy records and deployment-enforced status.', 'group' => 'Security and Privacy', 'navigation' => 'Security and Privacy', 'icon' => 'security'],
        'authentication' => ['label' => 'Authentication', 'description' => 'Password, MFA, session, and login protection settings.', 'group' => 'Security and Privacy', 'navigation' => 'Security and Privacy', 'icon' => 'authentication'],
        'notifications' => ['label' => 'Notifications', 'description' => 'Notification channels, routing groups, digest, and event toggles.', 'group' => 'Engagement', 'navigation' => 'Engagement', 'icon' => 'notifications'],
        'email' => ['label' => 'Email', 'description' => 'Non-secret email sender, reply-to, queue, and provider status.', 'group' => 'Platform', 'navigation' => 'Platform', 'icon' => 'email'],
        'integrations' => ['label' => 'Integrations', 'description' => 'Read-only integration health and safe adapter controls.', 'group' => 'Platform', 'navigation' => 'Platform', 'icon' => 'integrations'],
        'privacy' => ['label' => 'Privacy and Retention', 'description' => 'Consent, policy version, and retention limits.', 'group' => 'Security and Privacy', 'navigation' => 'Security and Privacy', 'icon' => 'privacy'],
        'content' => ['label' => 'Content and Publishing', 'description' => 'Publishing workflow, review, preview, and content behavior.', 'group' => 'Experience', 'navigation' => 'Experience', 'icon' => 'publishing'],
        'seo' => ['label' => 'SEO and Social Sharing', 'description' => 'Default metadata, indexing, sitemap, and structured-data controls.', 'group' => 'Experience', 'navigation' => 'Experience', 'icon' => 'seo'],
        'engagement' => ['label' => 'Engagement Forms', 'description' => 'Consultation, RFP, newsletter, career, and public form limits.', 'group' => 'Engagement', 'navigation' => 'Engagement', 'icon' => 'forms'],
        'media' => ['label' => 'Media and Uploads', 'description' => 'Approved upload limits and media processing status.', 'group' => 'Platform', 'navigation' => 'Platform', 'icon' => 'media'],
        'search' => ['label' => 'Search', 'description' => 'Search result limits, indexing behavior, and privacy-safe telemetry.', 'group' => 'Platform', 'navigation' => 'Platform', 'icon' => 'search'],
        'performance' => ['label' => 'Performance and Cache', 'description' => 'Safe cache lifetimes, pagination limits, and read-only runtime status.', 'group' => 'Platform', 'navigation' => 'Platform', 'icon' => 'performance'],
        'maintenance' => ['label' => 'Maintenance', 'description' => 'Public maintenance messaging and operational impact controls.', 'group' => 'Operations', 'navigation' => 'Operations', 'icon' => 'maintenance'],
        'features' => ['label' => 'Feature Flags', 'description' => 'Controlled feature switches with owners, review dates, and risk labels.', 'group' => 'Operations', 'navigation' => 'Operations', 'icon' => 'features'],
        'environment' => ['label' => 'Environment', 'description' => 'Read-only deployment and infrastructure configuration status.', 'group' => 'Operations', 'navigation' => 'Operations', 'icon' => 'environment'],
    ];

    /** @var array<string, bool|int|string|null> */
    public const DEFAULT_VALUES = [
        'site.name' => 'Impact Consulting Organization',
        'site.short_name' => 'Impact',
        'site.legal_name' => 'Impact Consulting Organization',
        'site.abbreviation' => 'ICO',
        'site.description' => 'Evidence-led consulting for lasting impact.',
        'site.email' => 'contact@example.test',
        'site.support_email' => 'support@example.test',
        'site.phone' => '+251 11 000 0000',
        'site.address' => 'Addis Ababa, Ethiopia',
        'site.postal_address' => null,
        'site.working_hours' => 'Monday to Friday, 09:00-17:00',
        'site.timezone' => 'Africa/Addis_Ababa',
        'site.default_country' => 'ET',
        'site.default_phone_country_code' => '+251',
        'site.copyright_owner' => 'Impact Consulting Organization',
        'site.copyright_start_year' => 2026,
        'site.public_environment_label' => null,
        'site.maintenance_contact' => 'support@example.test',

        'branding.primary_color' => '#17324D',
        'branding.advisory_teal' => '#2D7A78',
        'branding.knowledge_blue' => '#2D6C99',
        'branding.selective_gold' => '#C99A2E',
        'branding.text_color' => '#1F2933',
        'branding.quiet_surface_color' => '#F2F4F6',
        'branding.default_border_color' => '#CDD5DC',
        'branding.logo_url' => null,
        'branding.favicon_url' => null,
        'branding.social_image_url' => null,
        'branding.logo_alt_text' => 'Impact Consulting Organization',
        'branding.footer_tagline' => 'Evidence-led consulting for lasting impact.',
        'branding.email_footer_identity' => 'Impact Consulting Organization',

        'appearance.default_theme' => 'light',
        'appearance.allow_user_theme' => true,
        'appearance.default_admin_sidebar_state' => 'expanded',
        'appearance.content_density' => 'comfortable',
        'appearance.table_density' => 'comfortable',
        'appearance.form_density' => 'comfortable',
        'appearance.button_style' => 'solid',
        'appearance.card_radius' => 'large',
        'appearance.reduced_motion_default' => false,
        'appearance.high_contrast_enhancement' => false,
        'appearance.sticky_public_header' => true,
        'appearance.sticky_admin_topbar' => true,
        'appearance.show_breadcrumbs' => true,
        'appearance.display_environment_label' => false,
        'appearance.display_admin_logo' => true,

        'homepage.hero.autoplay' => true,
        'homepage.hero.interval_seconds' => 7,
        'homepage.hero.pause_on_hover' => true,
        'homepage.hero.primary_destination' => 'consultation',
        'homepage.hero.secondary_destination' => 'case_studies',

        'homepage.hero.slide_1.enabled' => true,
        'homepage.hero.slide_1.order' => 10,
        'homepage.hero.slide_1.eyebrow_en' => 'Impact Intelligence',
        'homepage.hero.slide_1.eyebrow_am' => 'ኢምፓክት ኢንተለጀንስ',
        'homepage.hero.slide_1.heading_en' => 'Evidence for the decisions that shape institutions.',
        'homepage.hero.slide_1.heading_am' => 'ለተቋማት የወደፊት አቅጣጫ በሚወስኑ ውሳኔዎች ላይ የተመሠረተ ማስረጃ።',
        'homepage.hero.slide_1.summary_en' => 'We turn complex questions into focused strategy, stronger delivery and lasting capability.',
        'homepage.hero.slide_1.summary_am' => 'ውስብስብ ጥያቄዎችን ወደ ግልጽ ስትራቴጂ፣ ጠንካራ ትግበራ እና ዘላቂ አቅም እንለውጣለን።',
        'homepage.hero.slide_1.image' => null,

        'homepage.hero.slide_2.enabled' => true,
        'homepage.hero.slide_2.order' => 20,
        'homepage.hero.slide_2.eyebrow_en' => 'Advisory Architecture',
        'homepage.hero.slide_2.eyebrow_am' => 'የምክር መዋቅር',
        'homepage.hero.slide_2.heading_en' => 'From ambition to an operating system that delivers.',
        'homepage.hero.slide_2.heading_am' => 'ከራዕይ ወደ ውጤት የሚያመጣ የሥራ ሥርዓት።',
        'homepage.hero.slide_2.summary_en' => 'We connect strategy, institutions and evidence so priorities become measurable progress.',
        'homepage.hero.slide_2.summary_am' => 'ቅድሚያዎች ወደ ሚለካ እድገት እንዲቀየሩ ስትራቴጂን፣ ተቋማትን እና ማስረጃን እናገናኛለን።',
        'homepage.hero.slide_2.image' => null,

        'homepage.hero.slide_3.enabled' => true,
        'homepage.hero.slide_3.order' => 30,
        'homepage.hero.slide_3.eyebrow_en' => 'Transformation Record',
        'homepage.hero.slide_3.eyebrow_am' => 'የለውጥ ማስረጃ',
        'homepage.hero.slide_3.heading_en' => 'Build capability that keeps creating value.',
        'homepage.hero.slide_3.heading_am' => 'ዋጋ መፍጠሩን የሚቀጥል አቅም ይገንቡ።',
        'homepage.hero.slide_3.summary_en' => 'Practical systems, stronger teams and learning rhythms make transformation durable.',
        'homepage.hero.slide_3.summary_am' => 'ተግባራዊ ሥርዓቶች፣ ጠንካራ ቡድኖች እና የመማር ልማዶች ለውጥን ዘላቂ ያደርጋሉ።',
        'homepage.hero.slide_3.image' => null,

        'localization.default_locale' => 'en',
        'localization.fallback_locale' => 'en',
        'localization.enabled_locales' => 'en,am',
        'localization.date_format' => 'Y-m-d',
        'localization.time_format' => 'H:i',
        'localization.first_day_of_week' => 'monday',
        'localization.missing_translation_behavior' => 'fallback',
        'localization.require_default_before_publication' => true,
        'localization.translation_review_interval_days' => 90,

        'security.contact_email' => 'security@example.test',
        'security.login_notice' => null,
        'security.review_interval_days' => 90,
        'security.incident_response_url' => null,
        'security.require_recent_mfa_high_risk' => true,
        'security.recent_mfa_valid_minutes' => 15,

        'authentication.password_min_length' => 12,
        'authentication.password_max_length' => 128,
        'authentication.require_uppercase' => false,
        'authentication.require_lowercase' => false,
        'authentication.require_number' => false,
        'authentication.require_symbol' => false,
        'authentication.prevent_common_passwords' => true,
        'authentication.password_reset_token_minutes' => 60,
        'authentication.max_login_attempts' => 5,
        'authentication.login_throttle_minutes' => 1,
        'authentication.require_mfa_privileged' => true,
        'authentication.mfa_challenge_lifetime_minutes' => 8,
        'authentication.recovery_code_count' => 8,
        'authentication.session_idle_timeout_minutes' => 120,
        'authentication.absolute_session_lifetime_minutes' => 480,
        'authentication.concurrent_session_limit' => 5,

        'notifications.database_enabled' => true,
        'notifications.email_enabled' => true,
        'notifications.sms_enabled' => false,
        'notifications.messaging_enabled' => false,
        'notifications.security_alerts' => true,
        'notifications.operational_alerts' => true,
        'notifications.engagement_alerts' => true,
        'notifications.recruitment_alerts' => true,
        'notifications.admin_alert_email' => 'operations@example.test',
        'notifications.security_recipients' => 'security@example.test',
        'notifications.operations_recipients' => 'operations@example.test',
        'notifications.hr_recipients' => 'hr@example.test',
        'notifications.engagement_submissions' => true,
        'notifications.application_submissions' => true,
        'notifications.newsletter_confirmations' => true,
        'notifications.digest_frequency' => 'daily',
        'notifications.retention_days' => 180,

        'email.enabled' => true,
        'email.from_name' => 'Impact Consulting Organization',
        'email.from_address' => 'hello@example.test',
        'email.reply_to' => 'contact@example.test',
        'email.default_locale' => 'en',
        'email.footer_text' => 'You are receiving this message from Impact Consulting Organization.',
        'email.queue_name' => 'notifications',
        'email.retry_attempts' => 3,

        'integrations.analytics_provider' => 'none',
        'integrations.search_provider' => 'database',
        'integrations.storage_provider' => 'local',
        'integrations.malware_scanner_enabled' => false,
        'integrations.maps_enabled' => false,
        'integrations.oidc_enabled' => false,
        'social.linkedin_url' => null,
        'analytics.enabled' => false,
        'analytics.provider_id' => null,

        'privacy.policy_version' => '2026-07-26',
        'privacy.cookie_notice_enabled' => true,
        'privacy.data_retention_days' => 730,
        'privacy.analytics_retention_days' => 30,
        'privacy.search_query_log_days' => 90,
        'privacy.require_marketing_consent' => true,

        'content.require_review_before_publication' => true,
        'content.prevent_self_approval' => true,
        'content.require_publication_reason' => true,
        'content.stale_content_threshold_days' => 180,
        'content.preview_expiry_minutes' => 60,
        'content.default_items_per_page' => 12,
        'content.auto_generate_slugs' => true,
        'content.accessibility_checks_required' => true,
        'content.seo_checks_required' => true,

        'seo.default_title' => 'Impact Consulting Organization',
        'seo.default_title_suffix' => 'Impact Consulting',
        'seo.default_description' => 'Evidence-led consulting for lasting impact.',
        'seo.robots_indexing' => true,
        'seo.sitemap_enabled' => true,
        'seo.sitemap_refresh_frequency' => 'daily',
        'seo.twitter_card_type' => 'summary_large_image',

        'engagement.consultation_form_enabled' => true,
        'engagement.rfp_form_enabled' => true,
        'engagement.contact_form_enabled' => true,
        'engagement.default_submission_locale' => 'en',
        'engagement.reference_prefix' => 'ICO',
        'engagement.acknowledgement_enabled' => true,
        'engagement.max_attachment_size_kb' => 20480,
        'engagement.max_attachment_count' => 5,
        'engagement.duplicate_submission_window_hours' => 24,

        'media.public_disk' => 'public',
        'media.private_disk' => 'local',
        'media.allowed_extensions' => 'pdf,docx,xlsx,pptx',
        'media.max_image_size_kb' => 5120,
        'media.malware_scanning_required' => false,
        'media.require_alt_text' => true,

        'search.enabled' => true,
        'search.results_per_page' => 10,
        'search.max_results_per_page' => 50,
        'search.log_queries' => true,
        'search.suggestion_limit' => 8,

        'performance.public_cache_enabled' => true,
        'performance.public_cache_lifetime_minutes' => 30,
        'performance.navigation_cache_lifetime_minutes' => 60,
        'performance.settings_cache_lifetime_minutes' => 60,
        'performance.default_pagination_size' => 15,
        'performance.maximum_pagination_size' => 100,

        'maintenance.maintenance_banner_enabled' => false,
        'maintenance.maintenance_banner_message' => null,
        'maintenance.public_title' => 'Scheduled maintenance',
        'maintenance.public_message' => 'We are performing scheduled maintenance. Please check back soon.',
        'maintenance.support_url' => null,
        'maintenance.allow_status_page' => true,

        'features.public_search_v2' => false,
        'features.enhanced_branding_previews' => true,
        'features.notification_digest' => true,
        'features.owner' => 'Platform administrator',
        'features.review_date' => '2026-10-01',
    ];

    /**
     * @var array<string, array{
     *     category: string,
     *     group: string,
     *     label: string,
     *     description: string,
     *     type: string,
     *     rule: list<string>,
     *     input?: string,
     *     options?: array<string, string>,
     *     scope?: string,
     *     sensitivity?: string,
     *     environment?: bool,
     *     restart?: string,
     *     permission?: string,
     *     recent_mfa?: bool,
     *     reason?: bool,
     *     audit?: string,
     *     effects?: list<string>,
     *     order?: int
     * }>
     */
    public const DEFINITIONS = [
        'site.name' => ['category' => 'general', 'group' => 'Organization identity', 'label' => 'Organization display name', 'description' => 'Primary name shown in public and admin shells.', 'type' => 'string', 'rule' => ['required', 'string', 'max:160'], 'effects' => ['immediate', 'affects_public_website'], 'order' => 10],
        'site.short_name' => ['category' => 'general', 'group' => 'Organization identity', 'label' => 'Application short name', 'description' => 'Compact label for constrained UI surfaces.', 'type' => 'string', 'rule' => ['required', 'string', 'max:60'], 'effects' => ['immediate'], 'order' => 20],
        'site.legal_name' => ['category' => 'general', 'group' => 'Organization identity', 'label' => 'Organization legal name', 'description' => 'Legal owner name used in footer and compliance text.', 'type' => 'string', 'rule' => ['required', 'string', 'max:180'], 'effects' => ['immediate', 'affects_public_website'], 'order' => 30],
        'site.abbreviation' => ['category' => 'general', 'group' => 'Organization identity', 'label' => 'Organization abbreviation', 'description' => 'Short institutional abbreviation.', 'type' => 'string', 'rule' => ['nullable', 'string', 'max:20'], 'order' => 40],
        'site.description' => ['category' => 'general', 'group' => 'Organization identity', 'label' => 'Organization description', 'description' => 'Concise organization description used where no page-specific summary exists.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:500'], 'input' => 'textarea', 'order' => 50],
        'site.email' => ['category' => 'general', 'group' => 'Contact information', 'label' => 'General inquiry email', 'description' => 'Public email for general inquiries.', 'type' => 'email', 'rule' => ['nullable', 'email:rfc', 'max:255'], 'input' => 'email', 'order' => 60],
        'site.support_email' => ['category' => 'general', 'group' => 'Contact information', 'label' => 'Support email', 'description' => 'Support contact for website and administrative issues.', 'type' => 'email', 'rule' => ['nullable', 'email:rfc', 'max:255'], 'input' => 'email', 'order' => 70],
        'site.phone' => ['category' => 'general', 'group' => 'Contact information', 'label' => 'Support phone', 'description' => 'Public support phone number.', 'type' => 'phone', 'rule' => ['nullable', 'string', 'max:40'], 'input' => 'tel', 'order' => 80],
        'site.address' => ['category' => 'general', 'group' => 'Contact information', 'label' => 'Physical address', 'description' => 'Primary public office address.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:500'], 'input' => 'textarea', 'order' => 90],
        'site.postal_address' => ['category' => 'general', 'group' => 'Contact information', 'label' => 'Postal address', 'description' => 'Optional postal address shown in contact details.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:500'], 'input' => 'textarea', 'order' => 100],
        'site.working_hours' => ['category' => 'general', 'group' => 'Contact information', 'label' => 'Working hours', 'description' => 'Public working hours text.', 'type' => 'string', 'rule' => ['nullable', 'string', 'max:160'], 'order' => 110],
        'site.timezone' => ['category' => 'general', 'group' => 'Operating defaults', 'label' => 'Organization timezone', 'description' => 'Default timezone for publication schedules and displayed operational dates.', 'type' => 'string', 'rule' => ['required', 'timezone'], 'environment' => true, 'effects' => ['requires_application_restart'], 'order' => 120],
        'site.default_country' => ['category' => 'general', 'group' => 'Operating defaults', 'label' => 'Default country', 'description' => 'Country code used for forms and formatting defaults.', 'type' => 'string', 'rule' => ['required', 'string', 'size:2'], 'order' => 130],
        'site.default_phone_country_code' => ['category' => 'general', 'group' => 'Operating defaults', 'label' => 'Default phone-country code', 'description' => 'Default dialing code shown near phone fields.', 'type' => 'phone', 'rule' => ['nullable', 'string', 'max:8'], 'order' => 140],
        'site.copyright_owner' => ['category' => 'general', 'group' => 'Legal identity', 'label' => 'Copyright owner', 'description' => 'Owner shown in the public footer.', 'type' => 'string', 'rule' => ['required', 'string', 'max:180'], 'effects' => ['immediate', 'affects_public_website'], 'order' => 150],
        'site.copyright_start_year' => ['category' => 'general', 'group' => 'Legal identity', 'label' => 'Copyright start year', 'description' => 'First year shown in legal footer text where applicable.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1900', 'max:2100'], 'input' => 'number', 'order' => 160],
        'site.public_environment_label' => ['category' => 'general', 'group' => 'Operating defaults', 'label' => 'Public environment label', 'description' => 'Optional non-secret environment label for non-production public pages.', 'type' => 'string', 'rule' => ['nullable', 'string', 'max:80'], 'environment' => true, 'order' => 170],
        'site.maintenance_contact' => ['category' => 'general', 'group' => 'Operating defaults', 'label' => 'Public maintenance contact', 'description' => 'Contact shown when maintenance information is displayed.', 'type' => 'email', 'rule' => ['nullable', 'email:rfc', 'max:255'], 'input' => 'email', 'order' => 180],

        'branding.primary_color' => ['category' => 'branding', 'group' => 'Approved palette', 'label' => 'Brand Navy', 'description' => 'Approved navy brand token.', 'type' => 'color', 'rule' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'input' => 'color', 'effects' => ['immediate', 'affects_public_website'], 'order' => 10],
        'branding.advisory_teal' => ['category' => 'branding', 'group' => 'Approved palette', 'label' => 'Advisory Teal', 'description' => 'Approved teal action token.', 'type' => 'color', 'rule' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'input' => 'color', 'order' => 20],
        'branding.knowledge_blue' => ['category' => 'branding', 'group' => 'Approved palette', 'label' => 'Knowledge Blue', 'description' => 'Approved informational and focus token.', 'type' => 'color', 'rule' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'input' => 'color', 'order' => 30],
        'branding.selective_gold' => ['category' => 'branding', 'group' => 'Approved palette', 'label' => 'Selective Gold', 'description' => 'Approved premium detail and warning token.', 'type' => 'color', 'rule' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'input' => 'color', 'order' => 40],
        'branding.text_color' => ['category' => 'branding', 'group' => 'Approved palette', 'label' => 'Primary text color', 'description' => 'Approved ink color token.', 'type' => 'color', 'rule' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'input' => 'color', 'order' => 50],
        'branding.quiet_surface_color' => ['category' => 'branding', 'group' => 'Approved palette', 'label' => 'Quiet surface color', 'description' => 'Approved grouped-control surface token.', 'type' => 'color', 'rule' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'input' => 'color', 'order' => 60],
        'branding.default_border_color' => ['category' => 'branding', 'group' => 'Approved palette', 'label' => 'Default border color', 'description' => 'Approved border token.', 'type' => 'color', 'rule' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'input' => 'color', 'order' => 70],
        'branding.logo_url' => ['category' => 'branding', 'group' => 'Brand assets', 'label' => 'Primary logo image', 'description' => 'Browse and upload the approved public logo asset.', 'type' => 'media_reference', 'rule' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/svg+xml', 'max:5120'], 'input' => 'image', 'accept' => 'image/jpeg,image/png,image/webp,image/svg+xml', 'order' => 80],
        'branding.favicon_url' => ['category' => 'branding', 'group' => 'Brand assets', 'label' => 'Favicon image', 'description' => 'Browse and upload the browser icon asset.', 'type' => 'media_reference', 'rule' => ['nullable', 'file', 'mimetypes:image/png,image/svg+xml,image/x-icon,image/vnd.microsoft.icon', 'max:1024'], 'input' => 'image', 'accept' => 'image/png,image/svg+xml,image/x-icon,image/vnd.microsoft.icon,.ico', 'order' => 90],
        'branding.social_image_url' => ['category' => 'branding', 'group' => 'Brand assets', 'label' => 'Default social-sharing image', 'description' => 'Browse and upload the fallback Open Graph image.', 'type' => 'media_reference', 'rule' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'], 'input' => 'image', 'accept' => 'image/jpeg,image/png,image/webp', 'order' => 100],
        'branding.logo_alt_text' => ['category' => 'branding', 'group' => 'Brand text', 'label' => 'Default logo alternative text', 'description' => 'Accessible text equivalent for logo images.', 'type' => 'string', 'rule' => ['required', 'string', 'max:160'], 'order' => 110],
        'branding.footer_tagline' => ['category' => 'branding', 'group' => 'Brand text', 'label' => 'Organization tagline', 'description' => 'Footer and compact brand tagline.', 'type' => 'string', 'rule' => ['nullable', 'string', 'max:180'], 'order' => 120],
        'branding.email_footer_identity' => ['category' => 'branding', 'group' => 'Brand text', 'label' => 'Email footer identity', 'description' => 'Identity text used in transactional email footer previews.', 'type' => 'string', 'rule' => ['nullable', 'string', 'max:180'], 'order' => 130],

        'appearance.default_theme' => ['category' => 'appearance', 'group' => 'Theme', 'label' => 'Default theme', 'description' => 'Default interface theme token.', 'type' => 'enum', 'rule' => ['required', 'in:light,dark,system'], 'input' => 'select', 'options' => ['light' => 'Light', 'dark' => 'Dark', 'system' => 'Use system preference'], 'order' => 10],
        'appearance.allow_user_theme' => ['category' => 'appearance', 'group' => 'Theme', 'label' => 'Allow users to change theme', 'description' => 'Allows users to select a personal theme preference.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 20],
        'appearance.default_admin_sidebar_state' => ['category' => 'appearance', 'group' => 'Admin shell', 'label' => 'Default admin sidebar state', 'description' => 'Initial admin sidebar state before a user preference exists.', 'type' => 'enum', 'rule' => ['required', 'in:expanded,collapsed'], 'input' => 'select', 'options' => ['expanded' => 'Expanded', 'collapsed' => 'Collapsed'], 'order' => 30],
        'appearance.content_density' => ['category' => 'appearance', 'group' => 'Density', 'label' => 'CMS content density', 'description' => 'Default density for admin content blocks.', 'type' => 'enum', 'rule' => ['required', 'in:comfortable,compact'], 'input' => 'select', 'options' => ['comfortable' => 'Comfortable', 'compact' => 'Compact'], 'order' => 40],
        'appearance.table_density' => ['category' => 'appearance', 'group' => 'Density', 'label' => 'Table density', 'description' => 'Default density for administrative tables.', 'type' => 'enum', 'rule' => ['required', 'in:comfortable,compact'], 'input' => 'select', 'options' => ['comfortable' => 'Comfortable', 'compact' => 'Compact'], 'order' => 50],
        'appearance.form_density' => ['category' => 'appearance', 'group' => 'Density', 'label' => 'Form density', 'description' => 'Default spacing around form controls.', 'type' => 'enum', 'rule' => ['required', 'in:comfortable,compact'], 'input' => 'select', 'options' => ['comfortable' => 'Comfortable', 'compact' => 'Compact'], 'order' => 60],
        'appearance.button_style' => ['category' => 'appearance', 'group' => 'Shape', 'label' => 'Button style', 'description' => 'Approved button treatment.', 'type' => 'enum', 'rule' => ['required', 'in:solid,soft,outline'], 'input' => 'select', 'options' => ['solid' => 'Solid', 'soft' => 'Soft', 'outline' => 'Outline'], 'order' => 70],
        'appearance.card_radius' => ['category' => 'appearance', 'group' => 'Shape', 'label' => 'Card radius', 'description' => 'Approved card corner radius.', 'type' => 'enum', 'rule' => ['required', 'in:medium,large'], 'input' => 'select', 'options' => ['medium' => 'Medium', 'large' => 'Large'], 'order' => 80],
        'appearance.reduced_motion_default' => ['category' => 'appearance', 'group' => 'Accessibility', 'label' => 'Reduced-motion default', 'description' => 'Prefer reduced motion by default where no browser preference exists.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 90],
        'appearance.high_contrast_enhancement' => ['category' => 'appearance', 'group' => 'Accessibility', 'label' => 'High-contrast enhancement', 'description' => 'Strengthens visible edges and focus styling.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 100],
        'appearance.sticky_public_header' => ['category' => 'appearance', 'group' => 'Navigation', 'label' => 'Sticky public header', 'description' => 'Keeps public navigation reachable during page scroll.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 110],
        'appearance.sticky_admin_topbar' => ['category' => 'appearance', 'group' => 'Navigation', 'label' => 'Sticky admin top bar', 'description' => 'Keeps admin actions visible while scrolling.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 120],
        'appearance.show_breadcrumbs' => ['category' => 'appearance', 'group' => 'Navigation', 'label' => 'Show public breadcrumbs', 'description' => 'Displays breadcrumbs on applicable public interior pages.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 130],
        'appearance.display_environment_label' => ['category' => 'appearance', 'group' => 'Environment', 'label' => 'Display environment label', 'description' => 'Shows a non-secret environment label when configured.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'environment' => true, 'order' => 140],
        'appearance.display_admin_logo' => ['category' => 'appearance', 'group' => 'Admin shell', 'label' => 'Display organization logo in admin sidebar', 'description' => 'Shows the brand identity in the admin sidebar when space permits.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 150],

        'homepage.hero.autoplay' => ['category' => 'homepage', 'group' => 'Slider behavior', 'label' => 'Automatic rotation', 'description' => 'Moves to the next enabled slide automatically when reduced motion is not requested.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['immediate', 'affects_public_website'], 'order' => 10],
        'homepage.hero.interval_seconds' => ['category' => 'homepage', 'group' => 'Slider behavior', 'label' => 'Rotation interval', 'description' => 'Seconds each slide remains visible before automatic rotation.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:4', 'max:20'], 'input' => 'number', 'effects' => ['immediate', 'affects_public_website'], 'order' => 20],
        'homepage.hero.pause_on_hover' => ['category' => 'homepage', 'group' => 'Slider behavior', 'label' => 'Pause during interaction', 'description' => 'Pauses rotation while a pointer or keyboard focus is inside the hero.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['immediate', 'affects_public_website'], 'order' => 30],
        'homepage.hero.primary_destination' => ['category' => 'homepage', 'group' => 'Slider behavior', 'label' => 'Primary action destination', 'description' => 'Approved public destination used by the main action on every slide.', 'type' => 'enum', 'rule' => ['required', 'in:consultation,services,case_studies,industries,experts,insights,contact,none'], 'input' => 'select', 'options' => ['consultation' => 'Request a consultation', 'services' => 'Services', 'case_studies' => 'Case studies', 'industries' => 'Industries', 'experts' => 'Experts', 'insights' => 'Insights', 'contact' => 'Contact', 'none' => 'No action'], 'effects' => ['immediate', 'affects_public_website'], 'order' => 40],
        'homepage.hero.secondary_destination' => ['category' => 'homepage', 'group' => 'Slider behavior', 'label' => 'Secondary action destination', 'description' => 'Approved public destination used by the supporting action on every slide.', 'type' => 'enum', 'rule' => ['required', 'in:consultation,services,case_studies,industries,experts,insights,contact,none'], 'input' => 'select', 'options' => ['consultation' => 'Request a consultation', 'services' => 'Services', 'case_studies' => 'Case studies', 'industries' => 'Industries', 'experts' => 'Experts', 'insights' => 'Insights', 'contact' => 'Contact', 'none' => 'No action'], 'effects' => ['immediate', 'affects_public_website'], 'order' => 50],

        'homepage.hero.slide_1.enabled' => ['category' => 'homepage', 'group' => 'Slide 1', 'label' => 'Slide 1 enabled', 'description' => 'Includes this slide in the public hero rotation.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['immediate', 'affects_public_website'], 'order' => 100],
        'homepage.hero.slide_1.order' => ['category' => 'homepage', 'group' => 'Slide 1', 'label' => 'Slide 1 order', 'description' => 'Lower numbers appear earlier. Duplicate values are resolved by slide number.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:100'], 'input' => 'number', 'effects' => ['immediate', 'affects_public_website'], 'order' => 110],
        'homepage.hero.slide_1.eyebrow_en' => ['category' => 'homepage', 'group' => 'Slide 1', 'label' => 'Slide 1 eyebrow — English', 'description' => 'Short English context label above the heading.', 'type' => 'string', 'rule' => ['required', 'string', 'max:80'], 'order' => 120],
        'homepage.hero.slide_1.eyebrow_am' => ['category' => 'homepage', 'group' => 'Slide 1', 'label' => 'Slide 1 eyebrow — Amharic', 'description' => 'Short Amharic context label above the heading.', 'type' => 'string', 'rule' => ['required', 'string', 'max:120'], 'order' => 130],
        'homepage.hero.slide_1.heading_en' => ['category' => 'homepage', 'group' => 'Slide 1', 'label' => 'Slide 1 heading — English', 'description' => 'Primary English statement for the first slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:180'], 'input' => 'textarea', 'order' => 140],
        'homepage.hero.slide_1.heading_am' => ['category' => 'homepage', 'group' => 'Slide 1', 'label' => 'Slide 1 heading — Amharic', 'description' => 'Primary Amharic statement for the first slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:240'], 'input' => 'textarea', 'order' => 150],
        'homepage.hero.slide_1.summary_en' => ['category' => 'homepage', 'group' => 'Slide 1', 'label' => 'Slide 1 summary — English', 'description' => 'Supporting English summary for the first slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:360'], 'input' => 'textarea', 'order' => 160],
        'homepage.hero.slide_1.summary_am' => ['category' => 'homepage', 'group' => 'Slide 1', 'label' => 'Slide 1 summary — Amharic', 'description' => 'Supporting Amharic summary for the first slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:480'], 'input' => 'textarea', 'order' => 170],
        'homepage.hero.slide_1.image' => ['category' => 'homepage', 'group' => 'Slide 1', 'label' => 'Slide 1 image', 'description' => 'Browse and upload a JPEG, PNG, or WebP hero image. The approved default is used when empty.', 'type' => 'media_reference', 'rule' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'], 'input' => 'image', 'accept' => 'image/jpeg,image/png,image/webp', 'effects' => ['immediate', 'affects_public_website'], 'order' => 180],

        'homepage.hero.slide_2.enabled' => ['category' => 'homepage', 'group' => 'Slide 2', 'label' => 'Slide 2 enabled', 'description' => 'Includes this slide in the public hero rotation.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['immediate', 'affects_public_website'], 'order' => 200],
        'homepage.hero.slide_2.order' => ['category' => 'homepage', 'group' => 'Slide 2', 'label' => 'Slide 2 order', 'description' => 'Lower numbers appear earlier. Duplicate values are resolved by slide number.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:100'], 'input' => 'number', 'effects' => ['immediate', 'affects_public_website'], 'order' => 210],
        'homepage.hero.slide_2.eyebrow_en' => ['category' => 'homepage', 'group' => 'Slide 2', 'label' => 'Slide 2 eyebrow — English', 'description' => 'Short English context label above the heading.', 'type' => 'string', 'rule' => ['required', 'string', 'max:80'], 'order' => 220],
        'homepage.hero.slide_2.eyebrow_am' => ['category' => 'homepage', 'group' => 'Slide 2', 'label' => 'Slide 2 eyebrow — Amharic', 'description' => 'Short Amharic context label above the heading.', 'type' => 'string', 'rule' => ['required', 'string', 'max:120'], 'order' => 230],
        'homepage.hero.slide_2.heading_en' => ['category' => 'homepage', 'group' => 'Slide 2', 'label' => 'Slide 2 heading — English', 'description' => 'Primary English statement for the second slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:180'], 'input' => 'textarea', 'order' => 240],
        'homepage.hero.slide_2.heading_am' => ['category' => 'homepage', 'group' => 'Slide 2', 'label' => 'Slide 2 heading — Amharic', 'description' => 'Primary Amharic statement for the second slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:240'], 'input' => 'textarea', 'order' => 250],
        'homepage.hero.slide_2.summary_en' => ['category' => 'homepage', 'group' => 'Slide 2', 'label' => 'Slide 2 summary — English', 'description' => 'Supporting English summary for the second slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:360'], 'input' => 'textarea', 'order' => 260],
        'homepage.hero.slide_2.summary_am' => ['category' => 'homepage', 'group' => 'Slide 2', 'label' => 'Slide 2 summary — Amharic', 'description' => 'Supporting Amharic summary for the second slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:480'], 'input' => 'textarea', 'order' => 270],
        'homepage.hero.slide_2.image' => ['category' => 'homepage', 'group' => 'Slide 2', 'label' => 'Slide 2 image', 'description' => 'Browse and upload a JPEG, PNG, or WebP hero image. The approved default is used when empty.', 'type' => 'media_reference', 'rule' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'], 'input' => 'image', 'accept' => 'image/jpeg,image/png,image/webp', 'effects' => ['immediate', 'affects_public_website'], 'order' => 280],

        'homepage.hero.slide_3.enabled' => ['category' => 'homepage', 'group' => 'Slide 3', 'label' => 'Slide 3 enabled', 'description' => 'Includes this slide in the public hero rotation.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['immediate', 'affects_public_website'], 'order' => 300],
        'homepage.hero.slide_3.order' => ['category' => 'homepage', 'group' => 'Slide 3', 'label' => 'Slide 3 order', 'description' => 'Lower numbers appear earlier. Duplicate values are resolved by slide number.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:100'], 'input' => 'number', 'effects' => ['immediate', 'affects_public_website'], 'order' => 310],
        'homepage.hero.slide_3.eyebrow_en' => ['category' => 'homepage', 'group' => 'Slide 3', 'label' => 'Slide 3 eyebrow — English', 'description' => 'Short English context label above the heading.', 'type' => 'string', 'rule' => ['required', 'string', 'max:80'], 'order' => 320],
        'homepage.hero.slide_3.eyebrow_am' => ['category' => 'homepage', 'group' => 'Slide 3', 'label' => 'Slide 3 eyebrow — Amharic', 'description' => 'Short Amharic context label above the heading.', 'type' => 'string', 'rule' => ['required', 'string', 'max:120'], 'order' => 330],
        'homepage.hero.slide_3.heading_en' => ['category' => 'homepage', 'group' => 'Slide 3', 'label' => 'Slide 3 heading — English', 'description' => 'Primary English statement for the third slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:180'], 'input' => 'textarea', 'order' => 340],
        'homepage.hero.slide_3.heading_am' => ['category' => 'homepage', 'group' => 'Slide 3', 'label' => 'Slide 3 heading — Amharic', 'description' => 'Primary Amharic statement for the third slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:240'], 'input' => 'textarea', 'order' => 350],
        'homepage.hero.slide_3.summary_en' => ['category' => 'homepage', 'group' => 'Slide 3', 'label' => 'Slide 3 summary — English', 'description' => 'Supporting English summary for the third slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:360'], 'input' => 'textarea', 'order' => 360],
        'homepage.hero.slide_3.summary_am' => ['category' => 'homepage', 'group' => 'Slide 3', 'label' => 'Slide 3 summary — Amharic', 'description' => 'Supporting Amharic summary for the third slide.', 'type' => 'text', 'rule' => ['required', 'string', 'max:480'], 'input' => 'textarea', 'order' => 370],
        'homepage.hero.slide_3.image' => ['category' => 'homepage', 'group' => 'Slide 3', 'label' => 'Slide 3 image', 'description' => 'Browse and upload a JPEG, PNG, or WebP hero image. The approved default is used when empty.', 'type' => 'media_reference', 'rule' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'], 'input' => 'image', 'accept' => 'image/jpeg,image/png,image/webp', 'effects' => ['immediate', 'affects_public_website'], 'order' => 380],

        'localization.default_locale' => ['category' => 'localization', 'group' => 'Locales', 'label' => 'Default locale', 'description' => 'Primary language for default routes and fallback content.', 'type' => 'enum', 'rule' => ['required', 'in:en,am'], 'input' => 'select', 'options' => ['en' => 'English', 'am' => 'Amharic'], 'environment' => true, 'order' => 10],
        'localization.fallback_locale' => ['category' => 'localization', 'group' => 'Locales', 'label' => 'Fallback locale', 'description' => 'Language used when localized content is unavailable.', 'type' => 'enum', 'rule' => ['required', 'in:en,am'], 'input' => 'select', 'options' => ['en' => 'English', 'am' => 'Amharic'], 'environment' => true, 'order' => 20],
        'localization.enabled_locales' => ['category' => 'localization', 'group' => 'Locales', 'label' => 'Enabled locales', 'description' => 'Comma-separated enabled locale codes. At least one supported locale is required.', 'type' => 'locale_list', 'rule' => ['required', 'regex:/^(en|am)(,(en|am))*$/'], 'order' => 30],
        'localization.date_format' => ['category' => 'localization', 'group' => 'Formats', 'label' => 'Date format', 'description' => 'Default PHP date format for administrative date display.', 'type' => 'string', 'rule' => ['required', 'string', 'max:40'], 'order' => 40],
        'localization.time_format' => ['category' => 'localization', 'group' => 'Formats', 'label' => 'Time format', 'description' => 'Default PHP time format for administrative time display.', 'type' => 'string', 'rule' => ['required', 'string', 'max:40'], 'order' => 50],
        'localization.first_day_of_week' => ['category' => 'localization', 'group' => 'Formats', 'label' => 'First day of week', 'description' => 'Calendar week start.', 'type' => 'enum', 'rule' => ['required', 'in:monday,sunday'], 'input' => 'select', 'options' => ['monday' => 'Monday', 'sunday' => 'Sunday'], 'order' => 60],
        'localization.missing_translation_behavior' => ['category' => 'localization', 'group' => 'Translation workflow', 'label' => 'Missing-translation behavior', 'description' => 'Controls safe display when content lacks a requested translation.', 'type' => 'enum', 'rule' => ['required', 'in:fallback,hide'], 'input' => 'select', 'options' => ['fallback' => 'Show fallback locale', 'hide' => 'Hide unavailable content'], 'order' => 70],
        'localization.require_default_before_publication' => ['category' => 'localization', 'group' => 'Translation workflow', 'label' => 'Require default-language version before publication', 'description' => 'Requires default locale content before publication can proceed.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 80],
        'localization.translation_review_interval_days' => ['category' => 'localization', 'group' => 'Translation workflow', 'label' => 'Translation-review reminder interval', 'description' => 'Number of days before translation review reminder.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:365'], 'input' => 'number', 'order' => 90],

        'security.contact_email' => ['category' => 'security', 'group' => 'Security center', 'label' => 'Security contact email', 'description' => 'Security contact route shown to administrators and in records.', 'type' => 'email', 'rule' => ['nullable', 'email:rfc', 'max:255'], 'input' => 'email', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['affects_security'], 'order' => 10],
        'security.login_notice' => ['category' => 'security', 'group' => 'Security center', 'label' => 'CMS login notice', 'description' => 'Operator-facing login notice. This does not weaken or replace authentication enforcement.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:500'], 'input' => 'textarea', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 20],
        'security.review_interval_days' => ['category' => 'security', 'group' => 'Security center', 'label' => 'Security review interval days', 'description' => 'Planned cadence for reviewing application-controlled security settings.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:365'], 'input' => 'number', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 30],
        'security.incident_response_url' => ['category' => 'security', 'group' => 'Security center', 'label' => 'Incident response URL', 'description' => 'Internal or public incident response documentation URL.', 'type' => 'url', 'rule' => ['nullable', 'url:http,https', 'max:500'], 'input' => 'url', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 40],
        'security.require_recent_mfa_high_risk' => ['category' => 'security', 'group' => 'Security center', 'label' => 'Require recent MFA for high-risk changes', 'description' => 'Requires recent MFA before sensitive settings can be saved.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['affects_security'], 'order' => 50],
        'security.recent_mfa_valid_minutes' => ['category' => 'security', 'group' => 'Security center', 'label' => 'Recent MFA validity period', 'description' => 'Number of minutes a fresh MFA challenge remains valid for high-risk settings.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:5', 'max:120'], 'input' => 'number', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['affects_security'], 'order' => 60],

        'authentication.password_min_length' => ['category' => 'authentication', 'group' => 'Password policy', 'label' => 'Minimum password length', 'description' => 'Application password minimum length.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:12', 'max:128'], 'input' => 'number', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['affects_security'], 'order' => 10],
        'authentication.password_max_length' => ['category' => 'authentication', 'group' => 'Password policy', 'label' => 'Maximum supported password length', 'description' => 'Maximum accepted password length.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:64', 'max:512'], 'input' => 'number', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 20],
        'authentication.require_uppercase' => ['category' => 'authentication', 'group' => 'Password policy', 'label' => 'Require uppercase', 'description' => 'Requires at least one uppercase letter when enabled.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 30],
        'authentication.require_lowercase' => ['category' => 'authentication', 'group' => 'Password policy', 'label' => 'Require lowercase', 'description' => 'Requires at least one lowercase letter when enabled.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 40],
        'authentication.require_number' => ['category' => 'authentication', 'group' => 'Password policy', 'label' => 'Require number', 'description' => 'Requires at least one number when enabled.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 50],
        'authentication.require_symbol' => ['category' => 'authentication', 'group' => 'Password policy', 'label' => 'Require symbol', 'description' => 'Requires at least one symbol when enabled.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 60],
        'authentication.prevent_common_passwords' => ['category' => 'authentication', 'group' => 'Password policy', 'label' => 'Prevent common passwords', 'description' => 'Blocks known weak passwords where enforced by the password rule layer.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 70],
        'authentication.password_reset_token_minutes' => ['category' => 'authentication', 'group' => 'Login protection', 'label' => 'Password-reset token lifetime', 'description' => 'Displayed application policy; broker TTL remains environment-managed in production.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:15', 'max:1440'], 'input' => 'number', 'environment' => true, 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['requires_application_restart'], 'order' => 80],
        'authentication.max_login_attempts' => ['category' => 'authentication', 'group' => 'Login protection', 'label' => 'Maximum login attempts', 'description' => 'Application-controlled login-attempt policy record.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:3', 'max:20'], 'input' => 'number', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 90],
        'authentication.login_throttle_minutes' => ['category' => 'authentication', 'group' => 'Login protection', 'label' => 'Login throttle duration', 'description' => 'Duration in minutes for login throttling policy.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:60'], 'input' => 'number', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 100],
        'authentication.require_mfa_privileged' => ['category' => 'authentication', 'group' => 'MFA', 'label' => 'Require MFA for privileged roles', 'description' => 'Privileged MFA enforcement is already active in middleware and cannot be weakened without code review.', 'type' => 'boolean', 'rule' => ['accepted'], 'environment' => true, 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['affects_security', 'affects_sessions'], 'order' => 110],
        'authentication.mfa_challenge_lifetime_minutes' => ['category' => 'authentication', 'group' => 'MFA', 'label' => 'MFA challenge lifetime', 'description' => 'Displayed MFA challenge lifetime in minutes.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:30'], 'input' => 'number', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 120],
        'authentication.recovery_code_count' => ['category' => 'authentication', 'group' => 'MFA', 'label' => 'Recovery-code count', 'description' => 'Number of recovery codes generated during enrollment.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:8', 'max:20'], 'input' => 'number', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 130],
        'authentication.session_idle_timeout_minutes' => ['category' => 'authentication', 'group' => 'Sessions', 'label' => 'Idle timeout', 'description' => 'Session idle timeout in minutes. Environment may remain authoritative.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:15', 'max:1440'], 'input' => 'number', 'environment' => true, 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['requires_application_restart', 'affects_sessions'], 'order' => 140],
        'authentication.absolute_session_lifetime_minutes' => ['category' => 'authentication', 'group' => 'Sessions', 'label' => 'Absolute session lifetime', 'description' => 'Absolute privileged session lifetime in minutes. Environment may remain authoritative.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:60', 'max:2880'], 'input' => 'number', 'environment' => true, 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['requires_application_restart', 'affects_sessions'], 'order' => 150],
        'authentication.concurrent_session_limit' => ['category' => 'authentication', 'group' => 'Sessions', 'label' => 'Concurrent-session limit', 'description' => 'Policy record for maximum concurrent sessions.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:20'], 'input' => 'number', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 160],

        'notifications.database_enabled' => ['category' => 'notifications', 'group' => 'Channels', 'label' => 'Enable database notifications', 'description' => 'Allows database notification records for non-critical events.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_notifications'], 'order' => 10],
        'notifications.email_enabled' => ['category' => 'notifications', 'group' => 'Channels', 'label' => 'Enable email notifications', 'description' => 'Allows email notification delivery where a notification uses mail.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_notifications'], 'order' => 20],
        'notifications.sms_enabled' => ['category' => 'notifications', 'group' => 'Channels', 'label' => 'Enable SMS notifications', 'description' => 'SMS remains disabled unless an approved adapter is configured.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'environment' => true, 'effects' => ['requires_deployment'], 'order' => 30],
        'notifications.messaging_enabled' => ['category' => 'notifications', 'group' => 'Channels', 'label' => 'Enable approved messaging channel', 'description' => 'Messaging remains disabled unless an approved adapter is configured.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'environment' => true, 'effects' => ['requires_deployment'], 'order' => 40],
        'notifications.security_alerts' => ['category' => 'notifications', 'group' => 'Mandatory alerts', 'label' => 'Enable security alerts', 'description' => 'Critical security alerts cannot be disabled without a protected override.', 'type' => 'boolean', 'rule' => ['accepted'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['affects_security', 'affects_notifications'], 'order' => 50],
        'notifications.operational_alerts' => ['category' => 'notifications', 'group' => 'Event alerts', 'label' => 'Enable operational alerts', 'description' => 'Controls operational alert delivery.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_notifications'], 'order' => 60],
        'notifications.engagement_alerts' => ['category' => 'notifications', 'group' => 'Event alerts', 'label' => 'Enable engagement alerts', 'description' => 'Controls consultation/RFP event alerts.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_notifications'], 'order' => 70],
        'notifications.recruitment_alerts' => ['category' => 'notifications', 'group' => 'Event alerts', 'label' => 'Enable recruitment alerts', 'description' => 'Controls careers/application event alerts.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_notifications'], 'order' => 80],
        'notifications.admin_alert_email' => ['category' => 'notifications', 'group' => 'Routing', 'label' => 'Admin alert email', 'description' => 'Default administrator notification recipient.', 'type' => 'email', 'rule' => ['nullable', 'email:rfc', 'max:255'], 'input' => 'email', 'effects' => ['affects_notifications'], 'order' => 90],
        'notifications.security_recipients' => ['category' => 'notifications', 'group' => 'Routing', 'label' => 'Security recipients', 'description' => 'Comma-separated security notification recipients.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:1000'], 'input' => 'textarea', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 100],
        'notifications.operations_recipients' => ['category' => 'notifications', 'group' => 'Routing', 'label' => 'Operations recipients', 'description' => 'Comma-separated operations notification recipients.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:1000'], 'input' => 'textarea', 'order' => 110],
        'notifications.hr_recipients' => ['category' => 'notifications', 'group' => 'Routing', 'label' => 'HR recipients', 'description' => 'Comma-separated HR notification recipients.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:1000'], 'input' => 'textarea', 'order' => 120],
        'notifications.engagement_submissions' => ['category' => 'notifications', 'group' => 'Event toggles', 'label' => 'New consultation received', 'description' => 'Notify when a consultation submission is received.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_notifications'], 'order' => 130],
        'notifications.application_submissions' => ['category' => 'notifications', 'group' => 'Event toggles', 'label' => 'New application received', 'description' => 'Notify when an application is received.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_notifications'], 'order' => 140],
        'notifications.newsletter_confirmations' => ['category' => 'notifications', 'group' => 'Event toggles', 'label' => 'Newsletter confirmation emails', 'description' => 'Send confirmation emails for newsletter double opt-in.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_notifications'], 'order' => 150],
        'notifications.digest_frequency' => ['category' => 'notifications', 'group' => 'Digest', 'label' => 'Digest frequency', 'description' => 'Default digest frequency for eligible events.', 'type' => 'enum', 'rule' => ['required', 'in:disabled,daily,weekly'], 'input' => 'select', 'options' => ['disabled' => 'Disabled', 'daily' => 'Daily', 'weekly' => 'Weekly'], 'effects' => ['affects_notifications'], 'order' => 160],
        'notifications.retention_days' => ['category' => 'notifications', 'group' => 'Retention', 'label' => 'Notification retention period', 'description' => 'Number of days to retain non-audit notification records.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:30', 'max:3650'], 'input' => 'number', 'order' => 170],

        'email.enabled' => ['category' => 'email', 'group' => 'Delivery behavior', 'label' => 'Email enabled', 'description' => 'Allows non-secret email delivery behavior where the configured provider is available.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_notifications'], 'order' => 10],
        'email.from_name' => ['category' => 'email', 'group' => 'Sender identity', 'label' => 'Email sender name', 'description' => 'Default non-secret sender display name.', 'type' => 'string', 'rule' => ['required', 'string', 'max:120'], 'environment' => true, 'effects' => ['requires_application_restart', 'affects_notifications'], 'order' => 20],
        'email.from_address' => ['category' => 'email', 'group' => 'Sender identity', 'label' => 'Sender email', 'description' => 'Default non-secret sender address display.', 'type' => 'email', 'rule' => ['required', 'email:rfc', 'max:255'], 'input' => 'email', 'environment' => true, 'effects' => ['requires_application_restart', 'affects_notifications'], 'order' => 30],
        'email.reply_to' => ['category' => 'email', 'group' => 'Sender identity', 'label' => 'Reply-to email', 'description' => 'Reply-to email address for public and transactional messages.', 'type' => 'email', 'rule' => ['nullable', 'email:rfc', 'max:255'], 'input' => 'email', 'effects' => ['affects_notifications'], 'order' => 40],
        'email.default_locale' => ['category' => 'email', 'group' => 'Templates', 'label' => 'Default email locale', 'description' => 'Default locale for system email previews.', 'type' => 'enum', 'rule' => ['required', 'in:en,am'], 'input' => 'select', 'options' => ['en' => 'English', 'am' => 'Amharic'], 'order' => 50],
        'email.footer_text' => ['category' => 'email', 'group' => 'Templates', 'label' => 'Email footer text', 'description' => 'Footer copy used in email template preview.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:500'], 'input' => 'textarea', 'order' => 60],
        'email.queue_name' => ['category' => 'email', 'group' => 'Queue', 'label' => 'Email queue name', 'description' => 'Queue name for notification jobs; worker configuration remains environment-managed.', 'type' => 'string', 'rule' => ['required', 'string', 'max:80'], 'environment' => true, 'effects' => ['requires_queue_restart'], 'order' => 70],
        'email.retry_attempts' => ['category' => 'email', 'group' => 'Queue', 'label' => 'Retry attempts', 'description' => 'Approved retry attempts within safe operational limits.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:10'], 'input' => 'number', 'order' => 80],

        'integrations.analytics_provider' => ['category' => 'integrations', 'group' => 'Providers', 'label' => 'Analytics provider', 'description' => 'Configured analytics adapter name. Credentials are environment-managed.', 'type' => 'enum', 'rule' => ['required', 'in:none,internal,external'], 'input' => 'select', 'options' => ['none' => 'None', 'internal' => 'Internal privacy-safe', 'external' => 'External configured provider'], 'environment' => true, 'effects' => ['requires_deployment'], 'order' => 10],
        'integrations.search_provider' => ['category' => 'integrations', 'group' => 'Providers', 'label' => 'Search provider', 'description' => 'Search provider status. Credentials are never exposed.', 'type' => 'enum', 'rule' => ['required', 'in:database,external'], 'input' => 'select', 'options' => ['database' => 'Database', 'external' => 'External configured provider'], 'environment' => true, 'effects' => ['requires_deployment'], 'order' => 20],
        'integrations.storage_provider' => ['category' => 'integrations', 'group' => 'Providers', 'label' => 'Object storage provider', 'description' => 'Storage provider status. Object-store credentials are environment-managed.', 'type' => 'enum', 'rule' => ['required', 'in:local,s3'], 'input' => 'select', 'options' => ['local' => 'Local', 's3' => 'S3 compatible'], 'environment' => true, 'effects' => ['requires_deployment'], 'order' => 30],
        'integrations.malware_scanner_enabled' => ['category' => 'integrations', 'group' => 'Security services', 'label' => 'Malware scanner enabled', 'description' => 'Displays malware scanner enablement status from safe configuration.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'environment' => true, 'sensitivity' => 'sensitive', 'effects' => ['requires_deployment', 'affects_security'], 'order' => 40],
        'integrations.maps_enabled' => ['category' => 'integrations', 'group' => 'Public services', 'label' => 'Maps enabled', 'description' => 'Maps display only when a privacy-approved provider is configured.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'environment' => true, 'effects' => ['requires_deployment'], 'order' => 50],
        'integrations.oidc_enabled' => ['category' => 'integrations', 'group' => 'Authentication providers', 'label' => 'OIDC enabled', 'description' => 'OIDC is displayed as configured or not configured; secrets remain outside settings.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'environment' => true, 'sensitivity' => 'sensitive', 'effects' => ['requires_deployment', 'affects_security'], 'order' => 60],
        'social.linkedin_url' => ['category' => 'integrations', 'group' => 'Legacy compatibility', 'label' => 'Legacy LinkedIn URL', 'description' => 'Preserved legacy value; public social links require an approved consumer before this becomes editable.', 'type' => 'string', 'rule' => ['nullable', 'url:http,https', 'max:500'], 'order' => 70],
        'analytics.enabled' => ['category' => 'integrations', 'group' => 'Legacy compatibility', 'label' => 'Legacy analytics enabled', 'description' => 'Preserved legacy value; the environment analytics provider and consent policy remain authoritative.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 80],
        'analytics.provider_id' => ['category' => 'integrations', 'group' => 'Legacy compatibility', 'label' => 'Legacy analytics provider identifier', 'description' => 'Preserved legacy identifier; the environment analytics provider remains authoritative.', 'type' => 'string', 'rule' => ['nullable', 'string', 'max:255'], 'order' => 90],

        'privacy.policy_version' => ['category' => 'privacy', 'group' => 'Consent', 'label' => 'Privacy notice version', 'description' => 'Changing this prompts visitors to review privacy choices again.', 'type' => 'string', 'rule' => ['required', 'date_format:Y-m-d'], 'input' => 'date', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['affects_public_website'], 'order' => 10],
        'privacy.cookie_notice_enabled' => ['category' => 'privacy', 'group' => 'Consent', 'label' => 'Cookie notice enabled', 'description' => 'Displays the consent notice when required.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 20],
        'privacy.data_retention_days' => ['category' => 'privacy', 'group' => 'Retention', 'label' => 'Default data retention days', 'description' => 'Default retention policy record for personal and submitted data.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:30', 'max:3650'], 'input' => 'number', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['affects_security'], 'order' => 30],
        'privacy.analytics_retention_days' => ['category' => 'privacy', 'group' => 'Retention', 'label' => 'Analytics retention days', 'description' => 'Retention for privacy-safe analytics outbox data.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:365'], 'input' => 'number', 'environment' => true, 'order' => 40],
        'privacy.search_query_log_days' => ['category' => 'privacy', 'group' => 'Retention', 'label' => 'Search query log days', 'description' => 'Retention for privacy-safe search query logs.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:365'], 'input' => 'number', 'environment' => true, 'order' => 50],
        'privacy.require_marketing_consent' => ['category' => 'privacy', 'group' => 'Consent', 'label' => 'Require marketing consent', 'description' => 'Requires explicit newsletter marketing consent.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 60],

        'content.require_review_before_publication' => ['category' => 'content', 'group' => 'Workflow', 'label' => 'Require review before publication', 'description' => 'Preserves documented workflow authorization.', 'type' => 'boolean', 'rule' => ['accepted'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 10],
        'content.prevent_self_approval' => ['category' => 'content', 'group' => 'Workflow', 'label' => 'Prevent self-approval', 'description' => 'Prevents authors from approving their own work.', 'type' => 'boolean', 'rule' => ['accepted'], 'environment' => true, 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 20],
        'content.require_publication_reason' => ['category' => 'content', 'group' => 'Workflow', 'label' => 'Require publication reason', 'description' => 'Requires a reason for publication transitions.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 30],
        'content.stale_content_threshold_days' => ['category' => 'content', 'group' => 'Governance', 'label' => 'Stale-content threshold', 'description' => 'Days after which content should be reviewed.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:30', 'max:730'], 'input' => 'number', 'order' => 40],
        'content.preview_expiry_minutes' => ['category' => 'content', 'group' => 'Preview', 'label' => 'Preview expiry', 'description' => 'Signed preview link lifetime in minutes.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:5', 'max:1440'], 'input' => 'number', 'order' => 50],
        'content.default_items_per_page' => ['category' => 'content', 'group' => 'Listings', 'label' => 'Default items per page', 'description' => 'Default public listing page size.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:50'], 'input' => 'number', 'order' => 60],
        'content.auto_generate_slugs' => ['category' => 'content', 'group' => 'Content behavior', 'label' => 'Auto-generate slugs', 'description' => 'Generate slugs from titles when no slug is provided.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 70],
        'content.accessibility_checks_required' => ['category' => 'content', 'group' => 'Quality gates', 'label' => 'Accessibility checks before publication', 'description' => 'Requires accessibility review before publication.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 80],
        'content.seo_checks_required' => ['category' => 'content', 'group' => 'Quality gates', 'label' => 'SEO checks before publication', 'description' => 'Requires SEO review before publication.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 90],

        'seo.default_title' => ['category' => 'seo', 'group' => 'Metadata defaults', 'label' => 'Default SEO title', 'description' => 'Title used when a page does not define one.', 'type' => 'string', 'rule' => ['required', 'string', 'max:70'], 'effects' => ['affects_public_website'], 'order' => 10],
        'seo.default_title_suffix' => ['category' => 'seo', 'group' => 'Metadata defaults', 'label' => 'Default SEO title suffix', 'description' => 'Suffix appended where templates support it.', 'type' => 'string', 'rule' => ['nullable', 'string', 'max:70'], 'order' => 20],
        'seo.default_description' => ['category' => 'seo', 'group' => 'Metadata defaults', 'label' => 'Default meta description', 'description' => 'Description used when a page lacks metadata.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:170'], 'input' => 'textarea', 'effects' => ['affects_public_website'], 'order' => 30],
        'seo.robots_indexing' => ['category' => 'seo', 'group' => 'Indexing', 'label' => 'Allow search indexing', 'description' => 'Default robots policy for public pages.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'environment' => true, 'effects' => ['affects_public_website'], 'order' => 40],
        'seo.sitemap_enabled' => ['category' => 'seo', 'group' => 'Sitemap', 'label' => 'Sitemap enabled', 'description' => 'Controls sitemap generation behavior where supported.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_public_website'], 'order' => 50],
        'seo.sitemap_refresh_frequency' => ['category' => 'seo', 'group' => 'Sitemap', 'label' => 'Sitemap refresh frequency', 'description' => 'Sitemap refresh cadence.', 'type' => 'enum', 'rule' => ['required', 'in:hourly,daily,weekly'], 'input' => 'select', 'options' => ['hourly' => 'Hourly', 'daily' => 'Daily', 'weekly' => 'Weekly'], 'order' => 60],
        'seo.twitter_card_type' => ['category' => 'seo', 'group' => 'Social sharing', 'label' => 'Twitter/X card type', 'description' => 'Default card type for social sharing metadata.', 'type' => 'enum', 'rule' => ['required', 'in:summary,summary_large_image'], 'input' => 'select', 'options' => ['summary' => 'Summary', 'summary_large_image' => 'Summary with large image'], 'order' => 70],

        'engagement.consultation_form_enabled' => ['category' => 'engagement', 'group' => 'Forms', 'label' => 'Consultation form enabled', 'description' => 'Allows public consultation submissions.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_public_website'], 'order' => 10],
        'engagement.rfp_form_enabled' => ['category' => 'engagement', 'group' => 'Forms', 'label' => 'RFP form enabled', 'description' => 'Allows public RFP submissions.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_public_website'], 'order' => 20],
        'engagement.contact_form_enabled' => ['category' => 'engagement', 'group' => 'Forms', 'label' => 'General contact form enabled', 'description' => 'Allows public contact submissions.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_public_website'], 'order' => 30],
        'engagement.default_submission_locale' => ['category' => 'engagement', 'group' => 'Defaults', 'label' => 'Default submission locale', 'description' => 'Default locale for system-created submission records.', 'type' => 'enum', 'rule' => ['required', 'in:en,am'], 'input' => 'select', 'options' => ['en' => 'English', 'am' => 'Amharic'], 'order' => 40],
        'engagement.reference_prefix' => ['category' => 'engagement', 'group' => 'Defaults', 'label' => 'Public reference prefix', 'description' => 'Prefix for generated public references where supported.', 'type' => 'string', 'rule' => ['required', 'string', 'max:12'], 'order' => 50],
        'engagement.acknowledgement_enabled' => ['category' => 'engagement', 'group' => 'Acknowledgements', 'label' => 'Acknowledgement enabled', 'description' => 'Sends acknowledgement when public submissions are received.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_notifications'], 'order' => 60],
        'engagement.max_attachment_size_kb' => ['category' => 'engagement', 'group' => 'Attachments', 'label' => 'Maximum attachment size', 'description' => 'Maximum attachment size in KB. Application will enforce the lower of this and environment limits.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:20480'], 'input' => 'number', 'sensitivity' => 'sensitive', 'effects' => ['affects_security'], 'order' => 70],
        'engagement.max_attachment_count' => ['category' => 'engagement', 'group' => 'Attachments', 'label' => 'Maximum attachment count', 'description' => 'Maximum number of uploaded files per supported submission.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:10'], 'input' => 'number', 'sensitivity' => 'sensitive', 'effects' => ['affects_security'], 'order' => 80],
        'engagement.duplicate_submission_window_hours' => ['category' => 'engagement', 'group' => 'Protection', 'label' => 'Duplicate-submission window', 'description' => 'Hours for duplicate-submission detection policy.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:168'], 'input' => 'number', 'order' => 90],

        'media.public_disk' => ['category' => 'media', 'group' => 'Storage', 'label' => 'Public media disk', 'description' => 'Public disk name. Infrastructure controls actual storage credentials.', 'type' => 'string', 'rule' => ['required', 'string', 'max:80'], 'environment' => true, 'effects' => ['requires_deployment'], 'order' => 10],
        'media.private_disk' => ['category' => 'media', 'group' => 'Storage', 'label' => 'Private media disk', 'description' => 'Private disk name. Infrastructure controls actual storage credentials.', 'type' => 'string', 'rule' => ['required', 'string', 'max:80'], 'environment' => true, 'effects' => ['requires_deployment'], 'order' => 20],
        'media.allowed_extensions' => ['category' => 'media', 'group' => 'Uploads', 'label' => 'Allowed attachment types', 'description' => 'Comma-separated approved file extensions.', 'type' => 'string', 'rule' => ['required', 'regex:/^[a-z0-9]+(,[a-z0-9]+)*$/'], 'sensitivity' => 'sensitive', 'effects' => ['affects_security'], 'order' => 30],
        'media.max_image_size_kb' => ['category' => 'media', 'group' => 'Uploads', 'label' => 'Maximum image size', 'description' => 'Maximum public media image size in KB.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:20480'], 'input' => 'number', 'order' => 40],
        'media.malware_scanning_required' => ['category' => 'media', 'group' => 'Upload security', 'label' => 'Malware scanning required', 'description' => 'Requires malware scan before files can be approved when scanner is available.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'environment' => true, 'sensitivity' => 'sensitive', 'effects' => ['affects_security'], 'order' => 50],
        'media.require_alt_text' => ['category' => 'media', 'group' => 'Accessibility', 'label' => 'Require alt text', 'description' => 'Requires meaningful alternative text for images where applicable.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 60],

        'search.enabled' => ['category' => 'search', 'group' => 'Behavior', 'label' => 'Search enabled', 'description' => 'Allows public search entry points.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['affects_public_website'], 'order' => 10],
        'search.results_per_page' => ['category' => 'search', 'group' => 'Behavior', 'label' => 'Results per page', 'description' => 'Default search result page size.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:50'], 'input' => 'number', 'order' => 20],
        'search.max_results_per_page' => ['category' => 'search', 'group' => 'Behavior', 'label' => 'Maximum results per page', 'description' => 'Maximum permitted search page size.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:100'], 'input' => 'number', 'order' => 30],
        'search.log_queries' => ['category' => 'search', 'group' => 'Privacy-safe telemetry', 'label' => 'Log search queries', 'description' => 'Logs privacy-safe search queries without names, emails, phone numbers, or record IDs.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 40],
        'search.suggestion_limit' => ['category' => 'search', 'group' => 'Suggestions', 'label' => 'Suggestion limit', 'description' => 'Maximum number of search suggestions returned.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:20'], 'input' => 'number', 'order' => 50],

        'performance.public_cache_enabled' => ['category' => 'performance', 'group' => 'Cache', 'label' => 'Public page cache enabled', 'description' => 'Enables application-level public page cache where implemented.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'effects' => ['clears_cache'], 'order' => 10],
        'performance.public_cache_lifetime_minutes' => ['category' => 'performance', 'group' => 'Cache', 'label' => 'Public cache lifetime', 'description' => 'Public page cache lifetime in minutes.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:1440'], 'input' => 'number', 'effects' => ['clears_cache'], 'order' => 20],
        'performance.navigation_cache_lifetime_minutes' => ['category' => 'performance', 'group' => 'Cache', 'label' => 'Navigation cache lifetime', 'description' => 'Navigation cache lifetime in minutes.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:1440'], 'input' => 'number', 'effects' => ['clears_cache'], 'order' => 30],
        'performance.settings_cache_lifetime_minutes' => ['category' => 'performance', 'group' => 'Cache', 'label' => 'Settings cache lifetime', 'description' => 'Effective settings cache lifetime in minutes.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:1440'], 'input' => 'number', 'effects' => ['clears_cache'], 'order' => 40],
        'performance.default_pagination_size' => ['category' => 'performance', 'group' => 'Pagination', 'label' => 'Default pagination size', 'description' => 'Default admin pagination size.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:100'], 'input' => 'number', 'order' => 50],
        'performance.maximum_pagination_size' => ['category' => 'performance', 'group' => 'Pagination', 'label' => 'Maximum pagination size', 'description' => 'Maximum allowed pagination size.', 'type' => 'integer', 'rule' => ['required', 'integer', 'min:1', 'max:500'], 'input' => 'number', 'order' => 60],

        'maintenance.maintenance_banner_enabled' => ['category' => 'maintenance', 'group' => 'Banner', 'label' => 'Maintenance banner enabled', 'description' => 'Displays a public maintenance banner without enabling Laravel maintenance mode.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['affects_public_website'], 'order' => 10],
        'maintenance.maintenance_banner_message' => ['category' => 'maintenance', 'group' => 'Banner', 'label' => 'Maintenance banner message', 'description' => 'Public maintenance banner message.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:300'], 'input' => 'textarea', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'effects' => ['affects_public_website'], 'order' => 20],
        'maintenance.public_title' => ['category' => 'maintenance', 'group' => 'Maintenance page', 'label' => 'Public maintenance title', 'description' => 'Title used by maintenance information surfaces.', 'type' => 'string', 'rule' => ['required', 'string', 'max:120'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 30],
        'maintenance.public_message' => ['category' => 'maintenance', 'group' => 'Maintenance page', 'label' => 'Public maintenance message', 'description' => 'Message used by maintenance information surfaces.', 'type' => 'text', 'rule' => ['nullable', 'string', 'max:500'], 'input' => 'textarea', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 40],
        'maintenance.support_url' => ['category' => 'maintenance', 'group' => 'Support', 'label' => 'Support URL', 'description' => 'Optional URL for maintenance support information.', 'type' => 'url', 'rule' => ['nullable', 'url:http,https', 'max:500'], 'input' => 'url', 'order' => 50],
        'maintenance.allow_status_page' => ['category' => 'maintenance', 'group' => 'Support', 'label' => 'Allow status page', 'description' => 'Allows linking to a configured external status page.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 60],

        'features.public_search_v2' => ['category' => 'features', 'group' => 'Flags', 'label' => 'Public search v2', 'description' => 'Controlled feature flag for search presentation changes.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 10],
        'features.enhanced_branding_previews' => ['category' => 'features', 'group' => 'Flags', 'label' => 'Enhanced branding previews', 'description' => 'Enables expanded branding preview cards in settings.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 20],
        'features.notification_digest' => ['category' => 'features', 'group' => 'Flags', 'label' => 'Notification digest', 'description' => 'Enables digest presentation where supported.', 'type' => 'boolean', 'rule' => ['required', 'boolean'], 'order' => 30],
        'features.owner' => ['category' => 'features', 'group' => 'Governance', 'label' => 'Feature flag owner', 'description' => 'Default owner for feature-flag review.', 'type' => 'string', 'rule' => ['required', 'string', 'max:120'], 'order' => 40],
        'features.review_date' => ['category' => 'features', 'group' => 'Governance', 'label' => 'Feature flag review date', 'description' => 'Default review date for current feature flags.', 'type' => 'string', 'rule' => ['required', 'date_format:Y-m-d'], 'input' => 'date', 'sensitivity' => 'sensitive', 'recent_mfa' => true, 'reason' => true, 'order' => 50],
    ];

    /** @var array<string, string> */
    public const ENVIRONMENT_OVERRIDES = [
        'site.timezone' => 'app.timezone',
        'site.public_environment_label' => 'app.env',
        'localization.default_locale' => 'app.locale',
        'localization.fallback_locale' => 'app.fallback_locale',
        'authentication.password_reset_token_minutes' => 'auth.passwords.users.expire',
        'authentication.session_idle_timeout_minutes' => 'session.lifetime',
        'authentication.absolute_session_lifetime_minutes' => 'session.absolute_lifetime',
        'authentication.require_mfa_privileged' => 'impact.security.privileged_mfa_required',
        'security.require_recent_mfa_high_risk' => 'impact.security.recent_mfa_required',
        'appearance.display_environment_label' => 'app.debug',
        'email.from_name' => 'mail.from.name',
        'email.from_address' => 'mail.from.address',
        'email.queue_name' => 'queue.connections.database.queue',
        'integrations.analytics_provider' => 'services.analytics.provider',
        'integrations.search_provider' => 'impact.search.provider',
        'integrations.storage_provider' => 'filesystems.default',
        'integrations.malware_scanner_enabled' => 'impact.files.malware_scanning_enabled',
        'integrations.maps_enabled' => 'services.maps.enabled',
        'integrations.oidc_enabled' => 'services.oidc.enabled',
        'privacy.analytics_retention_days' => 'impact.privacy.analytics_outbox_days',
        'privacy.search_query_log_days' => 'impact.privacy.search_query_log_days',
        'content.prevent_self_approval' => 'impact.workflow.prevent_self_approval',
        'media.public_disk' => 'impact.files.public_disk',
        'media.private_disk' => 'impact.files.private_disk',
        'media.malware_scanning_required' => 'impact.files.malware_scanning_enabled',
        'seo.robots_indexing' => 'app.env',
        'notifications.sms_enabled' => 'services.sms.enabled',
        'notifications.messaging_enabled' => 'services.messaging.enabled',
    ];

    /**
     * Controls retained for compatibility and audit visibility but intentionally
     * read-only until an approved runtime consumer exists.
     *
     * @var list<string>
     */
    public const INACTIVE_KEYS = [
        'site.default_country',
        'site.default_phone_country_code',
        'appearance.default_theme',
        'appearance.allow_user_theme',
        'appearance.content_density',
        'appearance.button_style',
        'localization.date_format',
        'localization.time_format',
        'localization.first_day_of_week',
        'localization.missing_translation_behavior',
        'localization.translation_review_interval_days',
        'security.review_interval_days',
        'security.incident_response_url',
        'authentication.concurrent_session_limit',
        'notifications.database_enabled',
        'notifications.security_alerts',
        'notifications.operational_alerts',
        'notifications.engagement_alerts',
        'notifications.recruitment_alerts',
        'notifications.admin_alert_email',
        'notifications.security_recipients',
        'notifications.operations_recipients',
        'notifications.hr_recipients',
        'notifications.digest_frequency',
        'notifications.retention_days',
        'email.default_locale',
        'privacy.analytics_retention_days',
        'content.accessibility_checks_required',
        'content.seo_checks_required',
        'seo.sitemap_refresh_frequency',
        'performance.public_cache_enabled',
        'performance.public_cache_lifetime_minutes',
        'performance.navigation_cache_lifetime_minutes',
        'performance.default_pagination_size',
        'features.notification_digest',
        'features.enhanced_branding_previews',
        'features.owner',
        'features.review_date',
        'social.linkedin_url',
        'analytics.enabled',
        'analytics.provider_id',
    ];

    public static function inputName(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    public static function categoryExists(string $category): bool
    {
        return array_key_exists($category, self::CATEGORIES);
    }

    public static function isEditable(string $key): bool
    {
        return isset(self::DEFINITIONS[$key])
            && ! array_key_exists($key, self::ENVIRONMENT_OVERRIDES)
            && ! in_array($key, self::INACTIVE_KEYS, true);
    }

    /** @return array<string, array<string, mixed>> */
    public static function forCategory(string $category): array
    {
        return collect(self::DEFINITIONS)
            ->filter(fn (array $definition): bool => $definition['category'] === $category)
            ->sortBy(fn (array $definition): int => (int) ($definition['order'] ?? 999))
            ->all();
    }

    /** @return list<string> */
    public static function keysForCategory(string $category): array
    {
        return array_keys(self::forCategory($category));
    }

    public static function categoryRequiresRecentMfa(string $category): bool
    {
        return collect(self::forCategory($category))->contains(
            fn (array $definition): bool => ($definition['recent_mfa'] ?? false) === true
        );
    }

    public static function categoryRequiresReason(string $category): bool
    {
        return collect(self::forCategory($category))->contains(
            fn (array $definition): bool => ($definition['reason'] ?? false) === true
        );
    }

    public static function categoryDefinition(string $category): array
    {
        return self::CATEGORIES[$category] ?? [
            'label' => 'Unknown settings',
            'description' => 'This settings category is not registered.',
            'group' => 'Other',
            'navigation' => 'Other',
            'icon' => 'settings',
        ];
    }

    public static function type(string $key): SettingType
    {
        return SettingType::from(self::DEFINITIONS[$key]['type']);
    }

    public static function category(string $key): SettingCategory
    {
        return SettingCategory::from(self::DEFINITIONS[$key]['category']);
    }

    public static function scope(string $key): SettingScope
    {
        return SettingScope::from(self::DEFINITIONS[$key]['scope'] ?? SettingScope::Global->value);
    }

    public static function sensitivity(string $key): SettingSensitivity
    {
        return SettingSensitivity::from(self::DEFINITIONS[$key]['sensitivity'] ?? SettingSensitivity::Internal->value);
    }

    /** @return list<SettingEffect> */
    public static function effects(string $key): array
    {
        return collect(self::DEFINITIONS[$key]['effects'] ?? [SettingEffect::Immediate->value])
            ->map(fn (string $effect): SettingEffect => SettingEffect::from($effect))
            ->values()
            ->all();
    }

    public static function navigationGroups(): Collection
    {
        return collect(self::CATEGORIES)
            ->groupBy(fn (array $category): string => $category['navigation'], preserveKeys: true)
            ->map(fn (Collection $categories, string $label): array => [
                'label' => $label,
                'categories' => $categories,
            ])
            ->values();
    }

    /** @return array<string, mixed> */
    public static function environmentSnapshot(): array
    {
        return [
            'app_environment' => app()->environment(),
            'app_debug' => config('app.debug') ? 'Enabled' : 'Disabled',
            'app_url' => config('app.url'),
            'app_timezone' => config('app.timezone'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'session_driver' => config('session.driver'),
            'session_secure_cookie' => config('session.secure') ? 'Enabled' : 'Environment/default',
            'same_site_policy' => config('session.same_site'),
            'email_provider' => config('mail.default'),
            'storage_driver' => config('filesystems.default'),
            'configuration_cache' => app()->configurationIsCached() ? 'Cached' : 'Not cached',
            'route_cache' => app()->routesAreCached() ? 'Cached' : 'Not cached',
            'view_cache_path' => 'Managed by Laravel view cache',
        ];
    }
}
