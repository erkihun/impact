<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Data\Seo\SeoIssue;
use App\Data\Seo\SeoValidationResult;
use App\Enums\Seo\SeoIssueSeverity;

/**
 * Checks emitted JSON-LD against the properties search engines require for
 * the types this site uses. It validates shape, not truth: content itself
 * comes only from published fields.
 */
final class StructuredDataValidator
{
    /** @var array<string, list<string>> required properties per type */
    private const REQUIRED = [
        'Organization' => ['name', 'url'],
        'ProfessionalService' => ['name', 'url'],
        'Corporation' => ['name', 'url'],
        'NGO' => ['name', 'url'],
        'WebSite' => ['name', 'url'],
        'WebPage' => ['url', 'name'],
        'CollectionPage' => ['url', 'name'],
        'AboutPage' => ['url', 'name'],
        'ContactPage' => ['url', 'name'],
        'ProfilePage' => ['url', 'name', 'mainEntity'],
        'SearchResultsPage' => ['url', 'name'],
        'BreadcrumbList' => ['itemListElement'],
        'ItemList' => ['itemListElement'],
        'Service' => ['name', 'provider'],
        'Person' => ['name'],
        'Article' => ['headline', 'datePublished', 'author', 'publisher'],
        'NewsArticle' => ['headline', 'datePublished', 'author', 'publisher'],
        'Report' => ['headline', 'author'],
        'Event' => ['name', 'startDate', 'location', 'eventAttendanceMode'],
        'JobPosting' => ['title', 'description', 'datePosted', 'hiringOrganization', 'validThrough'],
        'FAQPage' => ['mainEntity'],
    ];

    /** @var list<string> properties this site must never emit */
    private const FORBIDDEN = ['aggregateRating', 'review', 'award'];

    /** @param  list<string>  $scripts  raw JSON-LD script contents */
    public function validateScripts(array $scripts, string $url): SeoValidationResult
    {
        $result = new SeoValidationResult;
        foreach ($scripts as $script) {
            $decoded = json_decode($script, true);
            if (! is_array($decoded)) {
                $result->add(new SeoIssue('structured-data-invalid-json', SeoIssueSeverity::Blocking, 'Structured data is not valid JSON.', $url));

                continue;
            }
            if (($decoded['@context'] ?? null) !== 'https://schema.org') {
                $result->add(new SeoIssue('structured-data-context', SeoIssueSeverity::Blocking, 'Structured data must use the https://schema.org context.', $url));
            }
            $nodes = isset($decoded['@graph']) && is_array($decoded['@graph']) ? $decoded['@graph'] : [$decoded];
            $result->merge($this->validateNodes($nodes, $url));
        }

        return $result;
    }

    /** @param  list<array<string, mixed>>  $nodes */
    public function validateNodes(array $nodes, string $url): SeoValidationResult
    {
        $result = new SeoValidationResult;
        $ids = [];
        foreach ($nodes as $node) {
            $type = $node['@type'] ?? null;
            if (! is_string($type) || $type === '') {
                $result->add(new SeoIssue('structured-data-missing-type', SeoIssueSeverity::Blocking, 'A structured-data node has no @type.', $url));

                continue;
            }
            if (isset($node['@id'])) {
                if (isset($ids[$node['@id']])) {
                    $result->add(new SeoIssue('structured-data-duplicate-id', SeoIssueSeverity::Warning, "Duplicate @id {$node['@id']}.", $url, $type));
                }
                $ids[$node['@id']] = true;
            }
            foreach (self::REQUIRED[$type] ?? [] as $property) {
                if (! isset($node[$property]) || $node[$property] === '' || $node[$property] === []) {
                    $result->add(new SeoIssue('structured-data-missing-property', SeoIssueSeverity::Blocking, "{$type} is missing required property \"{$property}\".", $url, $type));
                }
            }
            foreach (self::FORBIDDEN as $property) {
                if (isset($node[$property])) {
                    $result->add(new SeoIssue('structured-data-forbidden-property', SeoIssueSeverity::Blocking, "{$type} must not declare {$property}: the site has no verified source for it.", $url, $type));
                }
            }
            if ($type === 'BreadcrumbList') {
                foreach ((array) ($node['itemListElement'] ?? []) as $index => $item) {
                    if (($item['position'] ?? null) !== $index + 1 || ! filled($item['name'] ?? null)) {
                        $result->add(new SeoIssue('structured-data-breadcrumb', SeoIssueSeverity::Blocking, 'Breadcrumb items need consecutive positions and names.', $url, $type));
                    }
                }
            }
            if ($type === 'JobPosting' && isset($node['validThrough']) && strtotime((string) $node['validThrough']) < time()) {
                $result->add(new SeoIssue('structured-data-expired-job', SeoIssueSeverity::Blocking, 'JobPosting markup is present after the closing date.', $url, $type));
            }
            foreach (['url', 'item'] as $property) {
                if (isset($node[$property]) && is_string($node[$property]) && preg_match('#/(en|am)(/|$)#', (string) parse_url($node[$property], PHP_URL_PATH)) === 1) {
                    $result->add(new SeoIssue('structured-data-legacy-locale-url', SeoIssueSeverity::Blocking, 'Structured data references a retired language URL.', $url, $type));
                }
            }
        }

        return $result;
    }
}
