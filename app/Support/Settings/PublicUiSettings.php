<?php

declare(strict_types=1);

namespace App\Support\Settings;

final readonly class PublicUiSettings
{
    public function __construct(private EffectiveSettings $settings) {}

    /**
     * Settings-backed data exposed to the public presentation layer.
     *
     * Blade templates deliberately receive this resolved, typed structure
     * instead of reaching into the settings registry themselves.
     *
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        $enabledLocales = $this->settings->array('localization.enabled_locales');
        $currentLocale = app()->getLocale();
        $alternateLocale = collect($enabledLocales)
            ->first(fn (string $locale): bool => $locale !== $currentLocale);

        return [
            'identity' => $this->identity(),
            'presentation' => [
                'css_variables' => $this->inlineCssVariables(),
                'body_classes' => $this->bodyClasses(),
                'default_theme' => $this->settings->string('appearance.default_theme'),
                'allow_user_theme' => $this->settings->boolean('appearance.allow_user_theme'),
            ],
            'localization' => [
                'current' => $currentLocale,
                'enabled' => $enabledLocales,
                'alternate' => $alternateLocale,
                'default' => $this->settings->string('localization.default_locale'),
                'date_format' => $this->settings->string('localization.date_format'),
                'time_format' => $this->settings->string('localization.time_format'),
                'first_day_of_week' => $this->settings->string('localization.first_day_of_week'),
            ],
            'seo' => [
                'default_title' => $this->settings->string('seo.default_title'),
                'title_suffix' => $this->settings->string('seo.default_title_suffix'),
                'default_description' => $this->settings->string('seo.default_description'),
                'robots' => $this->settings->boolean('seo.robots_indexing')
                    ? 'index,follow'
                    : 'noindex,nofollow',
                'twitter_card_type' => $this->settings->string('seo.twitter_card_type'),
            ],
            'features' => [
                'search' => $this->settings->boolean('search.enabled'),
                'consultation' => $this->settings->boolean('engagement.consultation_form_enabled'),
                'rfp' => $this->settings->boolean('engagement.rfp_form_enabled'),
                'contact' => $this->settings->boolean('engagement.contact_form_enabled'),
            ],
            'privacy' => [
                'policy_version' => $this->settings->string('privacy.policy_version'),
                'cookie_notice_enabled' => $this->settings->boolean('privacy.cookie_notice_enabled'),
            ],
            'maintenance' => [
                'banner_enabled' => $this->settings->boolean('maintenance.maintenance_banner_enabled'),
                'banner_message' => $this->settings->nullableString('maintenance.maintenance_banner_message')
                    ?: $this->settings->string('maintenance.public_message'),
                'status_page_enabled' => $this->settings->boolean('maintenance.allow_status_page'),
            ],
        ];
    }

    /** @return array<string, string> */
    public function cssVariables(): array
    {
        $brand = $this->settings->string('branding.primary_color');
        $action = $this->settings->string('branding.advisory_teal');
        $knowledge = $this->settings->string('branding.knowledge_blue');
        $gold = $this->settings->string('branding.selective_gold');
        $text = $this->settings->string('branding.text_color');
        $surface = $this->settings->string('branding.quiet_surface_color');
        $border = $this->settings->string('branding.default_border_color');

        return [
            '--color-primary-900' => $brand,
            '--color-secondary-700' => $action,
            '--color-primary-700' => $knowledge,
            '--color-accent-600' => $gold,
            '--color-text-default' => $text,
            '--color-surface-muted' => $surface,
            '--color-border-default' => $border,
            '--setting-card-radius' => $this->radius($this->settings->string('appearance.card_radius')),
            ...$this->runtimePaletteVariables(
                brand: $brand,
                action: $action,
                knowledge: $knowledge,
                gold: $gold,
                text: $text,
                surface: $surface,
                border: $border,
            ),
        ];
    }

    public function inlineCssVariables(): string
    {
        return collect($this->cssVariables())
            ->map(fn (string $value, string $token): string => "{$token}:{$value}")
            ->implode(';');
    }

    /** @return list<string> */
    public function bodyClasses(bool $admin = false): array
    {
        return array_values(array_filter([
            'theme-'.$this->settings->string('appearance.default_theme'),
            'density-content-'.$this->settings->string('appearance.content_density'),
            'density-table-'.$this->settings->string('appearance.table_density'),
            'density-form-'.$this->settings->string('appearance.form_density'),
            'buttons-'.$this->settings->string('appearance.button_style'),
            $this->settings->boolean('appearance.reduced_motion_default') ? 'setting-reduced-motion' : null,
            $this->settings->boolean('appearance.high_contrast_enhancement') ? 'setting-high-contrast' : null,
            $admin && ! $this->settings->boolean('appearance.sticky_admin_topbar') ? 'setting-admin-topbar-static' : null,
            ! $admin && ! $this->settings->boolean('appearance.sticky_public_header') ? 'setting-public-header-static' : null,
            ! $this->settings->boolean('appearance.show_breadcrumbs') ? 'setting-hide-breadcrumbs' : null,
        ]));
    }

    /** @return array<string, bool|string|null> */
    public function identity(): array
    {
        return [
            'name' => $this->settings->string('site.name'),
            'short_name' => $this->settings->string('site.short_name'),
            'legal_name' => $this->settings->string('site.legal_name'),
            'abbreviation' => $this->settings->nullableString('site.abbreviation'),
            'description' => $this->settings->nullableString('site.description'),
            'email' => $this->settings->nullableString('site.email'),
            'support_email' => $this->settings->nullableString('site.support_email'),
            'phone' => $this->settings->nullableString('site.phone'),
            'address' => $this->settings->nullableString('site.address'),
            'postal_address' => $this->settings->nullableString('site.postal_address'),
            'working_hours' => $this->settings->nullableString('site.working_hours'),
            'country' => $this->settings->string('site.default_country'),
            'phone_country_code' => $this->settings->nullableString('site.default_phone_country_code'),
            'copyright_owner' => $this->settings->string('site.copyright_owner'),
            'copyright_start_year' => (string) $this->settings->integer('site.copyright_start_year'),
            'maintenance_contact' => $this->settings->nullableString('site.maintenance_contact'),
            'logo' => $this->settings->mediaReference('branding.logo_url'),
            'favicon' => $this->settings->mediaReference('branding.favicon_url'),
            'social_image' => $this->settings->mediaReference('branding.social_image_url'),
            'logo_alt' => $this->settings->string('branding.logo_alt_text'),
            'tagline' => $this->settings->string('branding.footer_tagline'),
            'email_identity' => $this->settings->string('branding.email_footer_identity'),
            'display_admin_logo' => $this->settings->boolean('appearance.display_admin_logo'),
            'allow_user_theme' => $this->settings->boolean('appearance.allow_user_theme'),
        ];
    }

    /**
     * Tailwind needs RGB channels to retain support for opacity modifiers.
     * Default values preserve the approved palette exactly; custom anchor
     * colors produce a restrained tint-and-shade scale at request time.
     *
     * @return array<string, string>
     */
    private function runtimePaletteVariables(
        string $brand,
        string $action,
        string $knowledge,
        string $gold,
        string $text,
        string $surface,
        string $border,
    ): array {
        $palettes = [
            'brand' => $this->palette(
                anchor: $brand,
                defaultAnchor: '#17324D',
                anchorShade: 900,
                weights: [50 => .06, 100 => .14, 200 => .28, 300 => .46, 400 => .62, 500 => .74, 600 => .82, 700 => .88, 800 => .94, 900 => 1, 950 => .7],
                defaults: [50 => '#F2F6FA', 100 => '#DFE9F2', 200 => '#BFCFDE', 300 => '#91ACC2', 400 => '#6488A7', 500 => '#466B8B', 600 => '#35536E', 700 => '#294358', 800 => '#20374A', 900 => '#17324D', 950 => '#0E2235'],
            ),
            'action' => $this->palette(
                anchor: $action,
                defaultAnchor: '#2D7A78',
                anchorShade: 500,
                weights: [50 => .06, 100 => .14, 200 => .3, 300 => .5, 400 => .72, 500 => 1, 600 => .84, 700 => .68, 800 => .54, 900 => .42, 950 => .27],
                defaults: [50 => '#EEF8F7', 100 => '#D5ECEA', 200 => '#A9D8D4', 300 => '#75BDB7', 400 => '#489E98', 500 => '#2D7A78', 600 => '#246462', 700 => '#205250', 800 => '#1C4241', 900 => '#173736', 950 => '#0B2322'],
            ),
            'knowledge' => $this->palette(
                anchor: $knowledge,
                defaultAnchor: '#2D6C99',
                anchorShade: 600,
                weights: [50 => .06, 100 => .14, 200 => .3, 300 => .5, 400 => .7, 500 => .85, 600 => 1, 700 => .83, 800 => .68, 900 => .56, 950 => .36],
                defaults: [50 => '#EFF7FC', 100 => '#DCECF7', 200 => '#BDDCEE', 300 => '#91C4E1', 400 => '#5DA6CF', 500 => '#3788B7', 600 => '#2D6C99', 700 => '#28597B', 800 => '#264B66', 900 => '#243F55', 950 => '#182938'],
            ),
            'gold' => $this->palette(
                anchor: $gold,
                defaultAnchor: '#C99A2E',
                anchorShade: 500,
                weights: [50 => .06, 100 => .14, 200 => .3, 300 => .5, 400 => .72, 500 => 1, 600 => .84, 700 => .68, 800 => .54, 900 => .42, 950 => .27],
                defaults: [50 => '#FCF8EC', 100 => '#F7EDCE', 200 => '#EEDB9D', 300 => '#E4C364', 400 => '#D6AB3E', 500 => '#C99A2E', 600 => '#AA7724', 700 => '#88591F', 800 => '#714821', 900 => '#603D20', 950 => '#37200F'],
            ),
        ];

        $variables = [
            '--palette-text' => $this->rgbChannels($text),
            '--palette-muted' => $this->rgbChannels('#5D6A74'),
            '--palette-surface-muted' => $this->rgbChannels($surface),
            '--palette-border' => $this->rgbChannels($border),
        ];

        foreach ($palettes as $name => $palette) {
            foreach ($palette as $shade => $channels) {
                $variables["--palette-{$name}-{$shade}"] = $channels;
            }
        }

        return $variables;
    }

    /**
     * @param  array<int, float|int>  $weights
     * @param  array<int, string>  $defaults
     * @return array<int, string>
     */
    private function palette(
        string $anchor,
        string $defaultAnchor,
        int $anchorShade,
        array $weights,
        array $defaults,
    ): array {
        if (strtoupper($anchor) === strtoupper($defaultAnchor)) {
            return array_map($this->rgbChannels(...), $defaults);
        }

        $palette = [];

        foreach ($weights as $shade => $weight) {
            $palette[$shade] = match (true) {
                $shade < $anchorShade => $this->mixedRgbChannels($anchor, '#FFFFFF', (float) $weight),
                $shade > $anchorShade => $this->mixedRgbChannels($anchor, '#000000', (float) $weight),
                default => $this->rgbChannels($anchor),
            };
        }

        return $palette;
    }

    private function mixedRgbChannels(string $color, string $target, float $colorWeight): string
    {
        $sourceChannels = $this->channels($color);
        $targetChannels = $this->channels($target);

        return collect($sourceChannels)
            ->map(
                fn (int $channel, int $index): int => (int) round(
                    ($channel * $colorWeight) + ($targetChannels[$index] * (1 - $colorWeight)),
                ),
            )
            ->implode(' ');
    }

    private function rgbChannels(string $color): string
    {
        return implode(' ', $this->channels($color));
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function channels(string $color): array
    {
        $hex = ltrim($color, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function radius(string $radius): string
    {
        return match ($radius) {
            'none' => '0',
            'small' => '0.25rem',
            'medium' => '0.5rem',
            'large' => '0.75rem',
            'extra_large' => '1rem',
            default => '0.75rem',
        };
    }
}
