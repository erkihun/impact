<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Data\Seo\SeoMetadata;

/**
 * Converts resolved metadata into escaped <head> elements.
 *
 * The elements travel as the Inertia `seo.head` prop: the Blade root view
 * prints them for the first response, SSR prints them through the Inertia
 * head directive, and the client head manager swaps them on navigation.
 * Each element carries a stable data-inertia key so nothing is duplicated,
 * and empty values are never emitted.
 */
final readonly class SeoHeadRenderer
{
    public function __construct(
        private SeoSettings $settings,
        private StructuredDataBuilder $schema,
    ) {}

    /** @return list<string> */
    public function render(SeoMetadata $meta, bool $includeVerification = false): array
    {
        $socialTitle = $meta->socialTitle ?? $meta->title;
        $socialDescription = $meta->socialDescription ?? $meta->description;
        $tags = [
            '<title data-inertia="title">'.e($meta->title).'</title>',
            $this->meta('name', 'description', $meta->description),
            $this->meta('name', 'robots', $meta->robots->value),
            $meta->canonicalUrl ? '<link data-inertia="canonical" rel="canonical" href="'.e($meta->canonicalUrl).'">' : null,
            $this->meta('property', 'og:site_name', $this->settings->siteName()),
            $this->meta('property', 'og:locale', $this->settings->ogLocale()),
            $this->meta('property', 'og:type', $meta->ogType),
            $this->meta('property', 'og:title', $socialTitle),
            $this->meta('property', 'og:description', $socialDescription),
            $this->meta('property', 'og:url', $meta->canonicalUrl),
            $this->meta('property', 'og:image', $meta->image?->url),
            $this->meta('property', 'og:image:alt', $meta->image?->alt),
            $this->meta('property', 'og:image:width', $meta->image?->width ? (string) $meta->image->width : null),
            $this->meta('property', 'og:image:height', $meta->image?->height ? (string) $meta->image->height : null),
            $meta->ogType === 'article' ? $this->meta('property', 'article:published_time', $meta->publishedTime) : null,
            $meta->ogType === 'article' ? $this->meta('property', 'article:modified_time', $meta->modifiedTime) : null,
            $this->meta('name', 'twitter:card', $meta->image ? $this->settings->twitterCard() : 'summary'),
            $this->meta('name', 'twitter:site', $this->settings->twitterSite()),
            $this->meta('name', 'twitter:title', $socialTitle),
            $this->meta('name', 'twitter:description', $socialDescription),
            $this->meta('name', 'twitter:image', $meta->image?->url),
            $this->meta('name', 'twitter:image:alt', $meta->image?->alt),
        ];

        if ($includeVerification) {
            $tags[] = $this->meta('name', 'google-site-verification', $this->settings->googleVerification());
            $tags[] = $this->meta('name', 'msvalidate.01', $this->settings->bingVerification());
        }

        if ($meta->structuredData !== []) {
            $tags[] = '<script type="application/ld+json" data-inertia="structured-data">'.$this->schema->graph($meta->structuredData).'</script>';
        }

        return array_values(array_filter($tags));
    }

    /**
     * Minimal head for private, administrative and error responses.
     *
     * @return list<string>
     */
    public function privateHead(): array
    {
        return ['<meta data-inertia="robots" name="robots" content="noindex, nofollow">'];
    }

    private function meta(string $attribute, string $name, ?string $content): ?string
    {
        if ($content === null || trim($content) === '') {
            return null;
        }

        return '<meta data-inertia="'.e($name).'" '.$attribute.'="'.e($name).'" content="'.e($content).'">';
    }
}
