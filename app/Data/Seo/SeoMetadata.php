<?php

declare(strict_types=1);

namespace App\Data\Seo;

use App\Enums\Seo\RobotsDirective;

/**
 * Fully resolved metadata for one public response. Built by
 * SeoMetadataBuilder and rendered by SeoHeadRenderer; templates never
 * assemble metadata themselves.
 */
final readonly class SeoMetadata
{
    /**
     * @param  list<array<string, mixed>>  $structuredData  schema.org nodes for the @graph
     * @param  list<array{label: string, href: string|null}>  $breadcrumbs
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $canonicalUrl,
        public RobotsDirective $robots,
        public RobotsDirective $intendedRobots,
        public string $ogType = 'website',
        public ?string $socialTitle = null,
        public ?string $socialDescription = null,
        public ?SeoImage $image = null,
        public array $structuredData = [],
        public array $breadcrumbs = [],
        public ?string $publishedTime = null,
        public ?string $modifiedTime = null,
        public bool $sitemapEligible = false,
        public ?string $subjectType = null,
        public ?string $subjectKey = null,
    ) {}

    /** @return list<string> */
    public function structuredDataTypes(): array
    {
        return array_values(array_unique(array_map(
            static fn (array $node): string => is_array($node['@type'] ?? null)
                ? implode('/', $node['@type'])
                : (string) ($node['@type'] ?? ''),
            $this->structuredData,
        )));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonicalUrl,
            'robots' => $this->robots->value,
            'intendedRobots' => $this->intendedRobots->value,
            'ogType' => $this->ogType,
            'socialTitle' => $this->socialTitle ?? $this->title,
            'socialDescription' => $this->socialDescription ?? $this->description,
            'image' => $this->image?->toArray(),
            'structuredDataTypes' => $this->structuredDataTypes(),
            'sitemapEligible' => $this->sitemapEligible,
        ];
    }
}
