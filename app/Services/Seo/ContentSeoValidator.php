<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Data\Seo\SeoIssue;
use App\Data\Seo\SeoValidationResult;
use App\Enums\Seo\RobotsDirective;
use App\Enums\Seo\SeoIssueSeverity;
use Illuminate\Support\Str;

/**
 * Pre-publication SEO checks for one piece of content.
 *
 * Results are Blocking / Warning / Information. Only genuinely broken
 * states block publication: a missing title or heading, an invalid or
 * reserved slug, or an unsafe canonical override.
 */
final readonly class ContentSeoValidator
{
    public const TITLE_MIN = 15;

    public const TITLE_MAX = 65;

    public const DESCRIPTION_MIN = 50;

    public const DESCRIPTION_MAX = 160;

    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** First path segments that slugs and redirect sources must never take. */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'am', 'en', 'build', 'storage', 'login', 'logout', 'register', 'mfa', 'search',
        'sitemap', 'sitemaps', 'robots', 'dashboard', 'profile', 'new', 'create', 'edit', 'preview', 'draft',
    ];

    public function __construct(
        private SeoSettings $settings,
        private CanonicalUrlBuilder $urls,
    ) {}

    /**
     * @param  array{
     *     title?: string|null, seo_title?: string|null, description?: string|null,
     *     slug?: string|null, h1?: string|null, canonical?: string|null, robots?: string|null,
     *     images_missing_alt?: int, social_image?: bool, contextual_links?: int|null,
     *     structured_data?: list<string>
     * }  $input
     */
    public function validate(array $input): SeoValidationResult
    {
        $result = new SeoValidationResult;
        $title = $this->clean($input['seo_title'] ?? null) ?? $this->clean($input['title'] ?? null);
        $suffix = $this->settings->titleSuffix();

        if ($title === null) {
            $result->add(new SeoIssue('title-missing', SeoIssueSeverity::Blocking, 'Add a title. Search results need a unique, descriptive title.'));
        } else {
            $full = SeoMetadataBuilder::withSuffix($title, $suffix);
            if (mb_strlen($full) > self::TITLE_MAX) {
                $result->add(new SeoIssue('title-too-long', SeoIssueSeverity::Warning, 'The full title is '.mb_strlen($full).' characters and may be shortened in search results (aim for '.self::TITLE_MAX.' or fewer).'));
            }
            if (mb_strlen($title) < self::TITLE_MIN) {
                $result->add(new SeoIssue('title-too-short', SeoIssueSeverity::Warning, 'The title is very short; describe the page more specifically.'));
            }
        }

        $description = $this->clean($input['description'] ?? null);
        if ($description === null) {
            $result->add(new SeoIssue('description-missing', SeoIssueSeverity::Warning, 'Add an SEO description or summary; otherwise the site-wide fallback is used.'));
        } elseif (mb_strlen($description) < self::DESCRIPTION_MIN) {
            $result->add(new SeoIssue('description-too-short', SeoIssueSeverity::Warning, 'The description is shorter than '.self::DESCRIPTION_MIN.' characters.'));
        } elseif (mb_strlen($description) > self::DESCRIPTION_MAX) {
            $result->add(new SeoIssue('description-too-long', SeoIssueSeverity::Warning, 'The description will be shortened to '.self::DESCRIPTION_MAX.' characters in search results.'));
        }

        if (array_key_exists('slug', $input)) {
            $result->merge($this->validateSlug((string) $input['slug']));
        }

        if (array_key_exists('h1', $input) && $this->clean($input['h1']) === null) {
            $result->add(new SeoIssue('h1-missing', SeoIssueSeverity::Blocking, 'The page needs one visible main heading.'));
        }

        if (filled($input['canonical'] ?? null) && $this->urls->safeOverride((string) $input['canonical']) === null) {
            $result->add(new SeoIssue('canonical-unsafe', SeoIssueSeverity::Blocking, 'The canonical override must be a path or URL on this website.'));
        } elseif (filled($input['canonical'] ?? null)) {
            $result->add(new SeoIssue('canonical-overridden', SeoIssueSeverity::Information, 'A canonical override points search engines elsewhere; this page is excluded from the sitemap.'));
        }

        $robots = RobotsDirective::parse($input['robots'] ?? null);
        if ($robots !== null && ! $robots->isIndexable()) {
            $result->add(new SeoIssue('robots-noindex', SeoIssueSeverity::Information, 'This page is set to noindex and will not appear in search results or the sitemap.'));
        }

        if (($input['images_missing_alt'] ?? 0) > 0) {
            $result->add(new SeoIssue('image-alt-missing', SeoIssueSeverity::Warning, ($input['images_missing_alt']).' image(s) have no alternative text and are not marked decorative.'));
        }
        if (array_key_exists('social_image', $input) && ! $input['social_image']) {
            $result->add(new SeoIssue('social-image-default', SeoIssueSeverity::Information, 'No page image: the default social image will be used when shared.'));
        }
        if (array_key_exists('contextual_links', $input) && $input['contextual_links'] === 0) {
            $result->add(new SeoIssue('orphan-risk', SeoIssueSeverity::Warning, 'No related content links to this page. Add related services, industries, experts or insights.'));
        }
        if (($input['structured_data'] ?? []) !== []) {
            $result->add(new SeoIssue('structured-data-eligible', SeoIssueSeverity::Information, 'Structured data: '.implode(', ', $input['structured_data']).'.'));
        }

        return $result;
    }

    public function validateSlug(string $slug): SeoValidationResult
    {
        $result = new SeoValidationResult;
        if ($slug === '' || preg_match(self::SLUG_PATTERN, $slug) !== 1) {
            $result->add(new SeoIssue('slug-invalid', SeoIssueSeverity::Blocking, 'Use lowercase letters, numbers and single hyphens, for example "digital-transformation".'));
        } elseif (in_array($slug, self::RESERVED_SLUGS, true)) {
            $result->add(new SeoIssue('slug-reserved', SeoIssueSeverity::Blocking, "\"{$slug}\" is reserved by the website."));
        } elseif (mb_strlen($slug) > 80) {
            $result->add(new SeoIssue('slug-too-long', SeoIssueSeverity::Warning, 'Shorter slugs are easier to read and share.'));
        } elseif (preg_match('/^[0-9a-f]{8}-|^\d+$/', $slug) === 1) {
            $result->add(new SeoIssue('slug-not-descriptive', SeoIssueSeverity::Warning, 'Use descriptive words rather than identifiers.'));
        }

        return $result;
    }

    public static function normalizeSlug(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '-')->trim('-')->limit(80, '')->trim('-')->toString();
    }

    private function clean(?string $text): ?string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text)));

        return $text === '' ? null : $text;
    }
}
