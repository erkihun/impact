<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Data\Seo\SeoImage;
use App\Data\Seo\SeoMetadata;
use App\Enums\Seo\RobotsDirective;
use App\Models\MediaAsset;
use App\Models\SeoMetadata as SeoOverride;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Throwable;

/**
 * Resolves the metadata of one public response.
 *
 * Fallback order for every field: editor SEO override, then the content's
 * own title/summary/image, then the approved global default. Controllers
 * describe the page; this class decides the tags.
 */
final class SeoMetadataBuilder
{
    public const TITLE_MAX = 60;

    public const DESCRIPTION_MAX = 160;

    private ?string $pageTitle = null;

    private bool $home = false;

    /** @var list<string|null> */
    private array $descriptions = [];

    private ?string $canonical = null;

    private bool $withCanonical = true;

    /** @var list<string> */
    private array $meaningfulQuery = ['page'];

    private RobotsDirective $robots = RobotsDirective::IndexFollow;

    private string $ogType = 'website';

    private string $pageSchemaType = 'WebPage';

    private ?SeoImage $image = null;

    /** @var list<array{label: string, href: string|null}> */
    private array $breadcrumbs = [];

    /** @var list<array<string, mixed>> */
    private array $nodes = [];

    private bool $withStructuredData = true;

    private ?string $mainEntityId = null;

    private ?CarbonInterface $published = null;

    private ?CarbonInterface $modified = null;

    private ?string $subjectType = null;

    private ?string $subjectKey = null;

    private ?SeoOverride $override = null;

    private bool $listable = false;

    public function __construct(
        private readonly SeoSettings $settings,
        private readonly CanonicalUrlBuilder $urls,
        private readonly RobotsDirectiveBuilder $robotsBuilder,
        private readonly StructuredDataBuilder $schema,
        private readonly ResponsiveImages $images,
        private readonly Request $request,
    ) {}

    public static function make(): self
    {
        return app(self::class);
    }

    /** Attaches editor overrides stored for a page key or resource identity. */
    public function subject(string $type, string $key): self
    {
        $this->subjectType = $type;
        $this->subjectKey = $key;
        try {
            $this->override = SeoOverride::query()
                ->where('subject_type', $type)
                ->where('subject_key', $key)
                ->first();
        } catch (Throwable) {
            $this->override = null;
        }

        return $this;
    }

    public function home(): self
    {
        $this->home = true;

        return $this;
    }

    public function title(?string $title): self
    {
        $this->pageTitle = $this->clean($title);

        return $this;
    }

    public function description(?string ...$candidates): self
    {
        $this->descriptions = array_values($candidates);

        return $this;
    }

    public function canonical(?string $absoluteUrl): self
    {
        $this->canonical = $absoluteUrl;

        return $this;
    }

    public function withoutCanonical(): self
    {
        $this->withCanonical = false;

        return $this;
    }

    /** @param  list<string>  $keys */
    public function meaningfulQuery(array $keys): self
    {
        $this->meaningfulQuery = $keys;

        return $this;
    }

    public function robots(RobotsDirective $directive): self
    {
        $this->robots = $directive;

        return $this;
    }

    /** Marks the page as a sitemap candidate (published, canonical content). */
    public function listable(bool $listable = true): self
    {
        $this->listable = $listable;

        return $this;
    }

    public function type(string $ogType, string $pageSchemaType = 'WebPage'): self
    {
        $this->ogType = $ogType;
        $this->pageSchemaType = $pageSchemaType;

        return $this;
    }

    public function image(?SeoImage $image): self
    {
        $this->image ??= $image;

        return $this;
    }

    /** @param  list<array{label: string, href: string|null}>  $items */
    public function breadcrumbs(array $items): self
    {
        $this->breadcrumbs = $items;

        return $this;
    }

    /** @param  array<string, mixed>|null  ...$nodes */
    public function structuredData(?array ...$nodes): self
    {
        foreach ($nodes as $node) {
            if ($node !== null && $node !== []) {
                $this->nodes[] = $node;
            }
        }

        return $this;
    }

    public function withoutStructuredData(): self
    {
        $this->withStructuredData = false;

        return $this;
    }

    public function mainEntity(?string $id): self
    {
        $this->mainEntityId = $id;

        return $this;
    }

    public function dates(?CarbonInterface $published, ?CarbonInterface $modified = null): self
    {
        $this->published = $published;
        $this->modified = $modified;

        return $this;
    }

    public function canonicalUrl(): string
    {
        return $this->resolveCanonical() ?? $this->urls->forRequest($this->request, $this->meaningfulQuery);
    }

    public function build(): SeoMetadata
    {
        $intended = $this->robotsBuilder->intended($this->robots, $this->override?->robots);
        $canonical = $this->withCanonical ? $this->canonicalUrl() : null;
        $title = $this->fullTitle();
        $description = $this->resolveDescription();
        $image = $this->resolveImage();

        $structured = [];
        if ($this->withStructuredData && $canonical !== null) {
            $breadcrumb = $this->schema->breadcrumbList($canonical, $this->breadcrumbs);
            $structured = [
                $this->schema->organization(),
                $this->schema->website(),
                $this->schema->webPage(
                    $canonical,
                    $this->pageTitle ?? $title,
                    $description,
                    $this->pageSchemaType,
                    $breadcrumb !== null,
                    $image,
                    $this->mainEntityId,
                ),
                ...($breadcrumb !== null ? [$breadcrumb] : []),
                ...$this->nodes,
            ];
        }

        return new SeoMetadata(
            title: $title,
            description: $description,
            canonicalUrl: $canonical,
            robots: $this->robotsBuilder->effective($intended),
            intendedRobots: $intended,
            ogType: $this->ogType,
            socialTitle: $this->clean($this->override?->social_title),
            socialDescription: $this->normalizeDescription($this->override?->social_description),
            image: $image,
            structuredData: $structured,
            breadcrumbs: $this->breadcrumbs,
            publishedTime: $this->published?->toAtomString(),
            modifiedTime: ($this->modified ?? $this->published)?->toAtomString(),
            sitemapEligible: $this->listable && $intended->isIndexable() && ($this->override->include_in_sitemap ?? true)
                && ($canonical === null || $canonical === $this->urls->forRequest($this->request, $this->meaningfulQuery)),
            subjectType: $this->subjectType,
            subjectKey: $this->subjectKey,
        );
    }

    /**
     * "Page title | Suffix", with the suffix added exactly once. The homepage
     * uses its configured complete title.
     */
    public function fullTitle(): string
    {
        $override = $this->clean($this->override?->meta_title);
        if ($this->home && $override === null) {
            return $this->settings->homeTitle();
        }

        $base = $override ?? $this->pageTitle ?? $this->settings->fallbackTitle();
        $page = (int) $this->request->query('page', 1);
        if ($page > 1 && in_array('page', $this->meaningfulQuery, true)) {
            $base .= " – Page {$page}";
        }

        return self::withSuffix($base, $this->settings->titleSuffix());
    }

    /**
     * Appends the brand once. The match is case-sensitive so "Social impact
     * consulting" still gets "| Impact Consulting", while "About Impact
     * Consulting" does not repeat it. A title that would exceed the display
     * limit with the suffix keeps its descriptive part instead.
     */
    public static function withSuffix(string $base, string $suffix): string
    {
        if ($suffix === '' || str_ends_with($base, $suffix) || str_contains($base, "| {$suffix}")) {
            return $base;
        }
        $full = "{$base} | {$suffix}";

        return mb_strlen($full) > ContentSeoValidator::TITLE_MAX && mb_strlen($base) <= ContentSeoValidator::TITLE_MAX
            ? $base
            : $full;
    }

    private function resolveDescription(): ?string
    {
        foreach ([$this->override?->meta_description, ...$this->descriptions] as $candidate) {
            $normalized = $this->normalizeDescription($candidate);
            if ($normalized !== null) {
                $page = (int) $this->request->query('page', 1);

                return $page > 1 && in_array('page', $this->meaningfulQuery, true)
                    ? $this->normalizeDescription("Page {$page}. {$normalized}")
                    : $normalized;
            }
        }

        return $this->normalizeDescription($this->settings->defaultDescription());
    }

    private function resolveCanonical(): ?string
    {
        $override = $this->urls->safeOverride($this->override?->canonical_path);

        return $override ?? $this->canonical;
    }

    private function resolveImage(): ?SeoImage
    {
        $media = $this->override?->social_image_media_id
            ? MediaAsset::query()->find($this->override->social_image_media_id)
            : null;
        if ($media instanceof MediaAsset && $media->isPubliclyUsable()) {
            return new SeoImage($this->urls->absolute($media->publicUrl()), (string) ($media->alt_text ?: $this->settings->siteName()));
        }
        if ($this->image !== null) {
            return $this->image;
        }

        $default = $this->settings->defaultSocialImage();
        if ($default !== null) {
            return new SeoImage($this->urls->absolute($default), $this->settings->siteName());
        }

        $card = $this->images->socialCard('ethiopia-highlands');

        return $card === null ? null : new SeoImage(
            $this->urls->absolute($card['path']),
            $this->settings->siteName(),
            $card['width'],
            $card['height'],
            'image/jpeg',
        );
    }

    public function normalizeDescription(?string $text): ?string
    {
        $text = $this->clean($text);
        if ($text === null) {
            return null;
        }
        if (mb_strlen($text) <= self::DESCRIPTION_MAX) {
            return $text;
        }
        $cut = mb_substr($text, 0, self::DESCRIPTION_MAX - 1);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space !== false && $space > 100 ? mb_substr($cut, 0, $space) : $cut, ' ,;:.-–—').'…';
    }

    private function clean(?string $text): ?string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $text === '' ? null : $text;
    }
}
