<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Support\Settings\EffectiveSettings;
use Throwable;

/**
 * Approved resolver for every SEO-relevant setting.
 *
 * Values are read once per request (the class is registered as a scoped
 * binding) because EffectiveSettings resolves each key against the database.
 */
final class SeoSettings
{
    /** @var array<string, mixed> */
    private array $resolved = [];

    public function __construct(private readonly EffectiveSettings $settings) {}

    public function siteName(): string
    {
        return $this->string('site.name', 'Impact Consulting');
    }

    public function titleSuffix(): string
    {
        return $this->string('seo.default_title_suffix', 'Impact Consulting');
    }

    public function homeTitle(): string
    {
        return $this->string('seo.home_title', $this->titleSuffix());
    }

    public function homeDescription(): string
    {
        return $this->string('seo.home_description', $this->defaultDescription());
    }

    public function fallbackTitle(): string
    {
        return $this->string('seo.default_title', $this->siteName());
    }

    public function defaultDescription(): string
    {
        return $this->string('seo.default_description', '');
    }

    /**
     * Absolute base URL (scheme + host, no trailing slash) for canonical URLs.
     * Production always uses HTTPS.
     */
    public function canonicalBaseUrl(): string
    {
        $configured = $this->nullableString('seo.canonical_url') ?: (string) config('app.url');
        $parts = parse_url($configured);
        $host = strtolower((string) ($parts['host'] ?? 'localhost'));
        $scheme = app()->environment('production') ? 'https' : strtolower((string) ($parts['scheme'] ?? 'http'));
        $port = isset($parts['port']) && ! in_array((int) $parts['port'], [80, 443], true) ? ':'.$parts['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }

    public function canonicalHost(): string
    {
        return (string) parse_url($this->canonicalBaseUrl(), PHP_URL_HOST);
    }

    /** True only in production with indexing enabled. */
    public function indexingAllowed(): bool
    {
        return $this->boolean('seo.robots_indexing', false);
    }

    public function searchResultsNoindex(): bool
    {
        return $this->boolean('seo.search_results_noindex', true);
    }

    public function archivedContentIsGone(): bool
    {
        return $this->string('seo.archived_content_status', 'gone') === 'gone';
    }

    public function redirectSlugChanges(): bool
    {
        return $this->boolean('seo.redirect_published_slug_changes', true);
    }

    public function sitemapEnabled(): bool
    {
        return $this->boolean('seo.sitemap_enabled', true);
    }

    public function sitemapFrequency(): string
    {
        return $this->string('seo.sitemap_refresh_frequency', 'daily');
    }

    public function twitterCard(): string
    {
        return $this->string('seo.twitter_card_type', 'summary_large_image');
    }

    public function twitterSite(): ?string
    {
        return $this->nullableString('seo.twitter_site_handle');
    }

    public function ogLocale(): string
    {
        return $this->string('seo.og_locale', 'en_US');
    }

    public function organizationType(): string
    {
        return $this->string('seo.organization_type', 'Organization');
    }

    /** @return list<string> */
    public function socialProfiles(): array
    {
        return array_values(array_filter(array_map(
            fn (string $key): ?string => $this->nullableString($key),
            ['seo.social_linkedin_url', 'seo.social_x_url', 'seo.social_facebook_url', 'seo.social_youtube_url'],
        )));
    }

    public function googleVerification(): ?string
    {
        return $this->nullableString('seo.google_site_verification');
    }

    public function bingVerification(): ?string
    {
        return $this->nullableString('seo.bing_site_verification');
    }

    /** Public path or URL of the configured default social image. */
    public function defaultSocialImage(): ?string
    {
        return $this->remember('branding.social_image_url', fn (): ?string => $this->settings->mediaReference('branding.social_image_url'));
    }

    public function logo(): ?string
    {
        return $this->remember('branding.logo_url', fn (): ?string => $this->settings->mediaReference('branding.logo_url'));
    }

    /** @return array<string, string|null> */
    public function organization(): array
    {
        return [
            'name' => $this->siteName(),
            'legal_name' => $this->nullableString('site.legal_name'),
            'alternate_name' => $this->nullableString('site.abbreviation'),
            'description' => $this->nullableString('site.description'),
            'email' => $this->nullableString('site.email'),
            'phone' => $this->nullableString('site.phone'),
            'address' => $this->nullableString('site.address'),
            'postal_address' => $this->nullableString('site.postal_address'),
            'country' => $this->nullableString('site.default_country'),
        ];
    }

    private function string(string $key, string $fallback): string
    {
        $value = $this->nullableString($key);

        return $value === null || trim($value) === '' ? $fallback : $value;
    }

    private function nullableString(string $key): ?string
    {
        return $this->remember($key, function () use ($key): ?string {
            $value = $this->settings->effective($key);

            return is_string($value) && trim($value) !== '' ? trim($value) : null;
        });
    }

    private function boolean(string $key, bool $fallback): bool
    {
        return (bool) $this->remember($key, fn (): bool => (bool) $this->settings->effective($key), $fallback);
    }

    private function remember(string $key, callable $resolve, mixed $fallback = null): mixed
    {
        if (! array_key_exists($key, $this->resolved)) {
            try {
                $this->resolved[$key] = $resolve();
            } catch (Throwable) {
                // Settings must never take the public site down; use the safe default.
                $this->resolved[$key] = $fallback;
            }
        }

        return $this->resolved[$key];
    }
}
