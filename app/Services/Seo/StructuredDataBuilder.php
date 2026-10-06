<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Data\Seo\SeoImage;
use App\Models\CaseStudyVersion;
use App\Models\Event;
use App\Models\ExpertVersion;
use App\Models\InsightVersion;
use App\Models\ServiceVersion;
use App\Models\Vacancy;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Builds schema.org nodes from approved settings and visible, published
 * content. Nothing here invents ratings, reviews, awards or claims, and
 * editors cannot inject JSON-LD: every node is assembled from typed fields.
 */
final readonly class StructuredDataBuilder
{
    public function __construct(
        private SeoSettings $settings,
        private CanonicalUrlBuilder $canonical,
    ) {}

    public function organizationId(): string
    {
        return $this->canonical->url('/').'#organization';
    }

    public function websiteId(): string
    {
        return $this->canonical->url('/').'#website';
    }

    /** @return array<string, mixed> */
    public function organization(): array
    {
        $org = $this->settings->organization();
        $logo = $this->settings->logo();
        $address = $org['postal_address'] ?? $org['address'];

        return $this->clean([
            '@type' => $this->settings->organizationType(),
            '@id' => $this->organizationId(),
            'name' => $org['name'],
            'legalName' => $org['legal_name'] !== $org['name'] ? $org['legal_name'] : null,
            'alternateName' => $org['alternate_name'],
            'url' => $this->canonical->url('/'),
            'description' => $org['description'],
            'logo' => $logo ? ['@type' => 'ImageObject', 'url' => $this->canonical->absolute($logo)] : null,
            'email' => $org['email'],
            'telephone' => $org['phone'],
            'address' => $address ? $this->clean([
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressCountry' => $org['country'],
            ]) : null,
            'contactPoint' => ($org['email'] || $org['phone']) ? [$this->clean([
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'email' => $org['email'],
                'telephone' => $org['phone'],
                'availableLanguage' => 'English',
            ])] : null,
            'sameAs' => $this->settings->socialProfiles() ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    public function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => $this->websiteId(),
            'url' => $this->canonical->url('/'),
            'name' => $this->settings->siteName(),
            'inLanguage' => 'en',
            'publisher' => ['@id' => $this->organizationId()],
        ];
    }

    /** @return array<string, mixed> */
    public function webPage(
        string $url,
        string $name,
        ?string $description,
        string $type = 'WebPage',
        bool $hasBreadcrumb = false,
        ?SeoImage $image = null,
        ?string $mainEntityId = null,
    ): array {
        return $this->clean([
            '@type' => $type,
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $name,
            'description' => $description,
            'inLanguage' => 'en',
            'isPartOf' => ['@id' => $this->websiteId()],
            'breadcrumb' => $hasBreadcrumb ? ['@id' => $url.'#breadcrumb'] : null,
            'primaryImageOfPage' => $image ? ['@type' => 'ImageObject', 'url' => $image->url] : null,
            'mainEntity' => $mainEntityId ? ['@id' => $mainEntityId] : null,
        ]);
    }

    /**
     * @param  list<array{label: string, href: string|null}>  $items
     * @return array<string, mixed>|null
     */
    public function breadcrumbList(string $pageUrl, array $items): ?array
    {
        if (count($items) < 2) {
            return null;
        }

        $elements = [];
        foreach ($items as $index => $item) {
            $elements[] = $this->clean([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['label'],
                // Visible breadcrumbs may carry request-host URLs; markup always
                // uses the canonical host.
                'item' => $item['href'] !== null
                    ? $this->canonical->url((string) (parse_url($item['href'], PHP_URL_PATH) ?: '/'))
                    : $pageUrl,
            ]);
        }

        return ['@type' => 'BreadcrumbList', '@id' => $pageUrl.'#breadcrumb', 'itemListElement' => $elements];
    }

    /**
     * @param  list<string>  $areaServed
     * @return array<string, mixed>
     */
    public function service(ServiceVersion $service, string $url, array $areaServed = []): array
    {
        return $this->clean([
            '@type' => 'Service',
            '@id' => $url.'#service',
            'name' => $service->name,
            'serviceType' => $service->name,
            'description' => $this->plain($service->summary ?: $service->problem_statement),
            'url' => $url,
            'provider' => ['@id' => $this->organizationId()],
            'areaServed' => $areaServed ?: null,
        ]);
    }

    /**
     * @param  list<string>  $knowsAbout
     * @return array<string, mixed>
     */
    public function person(ExpertVersion $expert, string $url, ?SeoImage $image, array $knowsAbout = []): array
    {
        return $this->clean([
            '@type' => 'Person',
            '@id' => $url.'#person',
            'name' => $expert->display_name,
            'jobTitle' => $expert->professional_title,
            'description' => $this->plain($expert->biography, 300),
            'url' => $url,
            'image' => $image?->url,
            'worksFor' => ['@id' => $this->organizationId()],
            'knowsAbout' => $knowsAbout ?: null,
            'knowsLanguage' => $expert->languages ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    public function article(
        InsightVersion|CaseStudyVersion $content,
        string $url,
        ?SeoImage $image,
        ?CarbonInterface $published,
        ?CarbonInterface $modified,
    ): array {
        $type = 'Article';
        if ($content instanceof InsightVersion) {
            $type = match ((string) $content->insight?->type) {
                'news' => 'NewsArticle',
                'report', 'publication' => 'Report',
                default => 'Article',
            };
        }
        $headline = $content->title;

        return $this->clean([
            '@type' => $type,
            '@id' => $url.'#article',
            'headline' => Str::limit($headline, 110, ''),
            'name' => $headline,
            'description' => $this->plain($content instanceof InsightVersion ? $content->excerpt : $content->challenge),
            'articleSection' => $content instanceof CaseStudyVersion ? 'Case study' : null,
            'image' => $image ? [$image->url] : null,
            'datePublished' => $published?->toAtomString(),
            'dateModified' => ($modified ?? $published)?->toAtomString(),
            // Content has no named byline, so the organization is the author.
            'author' => ['@id' => $this->organizationId()],
            'publisher' => ['@id' => $this->organizationId()],
            'mainEntityOfPage' => ['@id' => $url.'#webpage'],
            'inLanguage' => 'en',
        ]);
    }

    /** @return array<string, mixed> */
    public function event(Event $event, string $url, ?SeoImage $image): array
    {
        $format = (string) $event->getRawOriginal('format');
        $mode = match ($format) {
            'online', 'virtual' => 'https://schema.org/OnlineEventAttendanceMode',
            'hybrid' => 'https://schema.org/MixedEventAttendanceMode',
            default => 'https://schema.org/OfflineEventAttendanceMode',
        };
        $locations = [];
        if ($format !== 'online' && $format !== 'virtual' && filled($event->venue)) {
            $locations[] = [
                '@type' => 'Place',
                'name' => $event->venue,
                'address' => $this->clean([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $event->venue,
                    'addressCountry' => $this->settings->organization()['country'],
                ]),
            ];
        }
        if (in_array($format, ['online', 'virtual', 'hybrid'], true)) {
            // The meeting link is private; the public event page is the entry point.
            $locations[] = ['@type' => 'VirtualLocation', 'url' => $url];
        }
        $timezone = (string) ($event->timezone ?: 'UTC');

        return $this->clean([
            '@type' => 'Event',
            '@id' => $url.'#event',
            'name' => $event->title,
            'description' => $this->plain($event->description, 300),
            'url' => $url,
            'startDate' => $event->starts_at->setTimezone($timezone)->toAtomString(),
            'endDate' => $event->ends_at->setTimezone($timezone)->toAtomString(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => $mode,
            'location' => count($locations) === 1 ? $locations[0] : ($locations ?: null),
            'image' => $image ? [$image->url] : null,
            'organizer' => ['@id' => $this->organizationId()],
        ]);
    }

    /** @return array<string, mixed>|null */
    public function jobPosting(Vacancy $vacancy, string $url): ?array
    {
        // Expired or closed postings must not be marked up as open jobs.
        if (! $vacancy->acceptsApplications()) {
            return null;
        }

        $org = $this->settings->organization();
        $logo = $this->settings->logo();

        return $this->clean([
            '@type' => 'JobPosting',
            '@id' => $url.'#job',
            'title' => $vacancy->title,
            'description' => nl2br(e(trim($vacancy->description."\n\n".$vacancy->requirements)), false),
            'datePosted' => ($vacancy->opens_at ?? $vacancy->created_at)?->toDateString(),
            'validThrough' => $vacancy->closes_at?->toAtomString(),
            'employmentType' => match ((string) $vacancy->type) {
                'full_time' => 'FULL_TIME',
                'part_time' => 'PART_TIME',
                'contract', 'consultancy' => 'CONTRACTOR',
                'temporary' => 'TEMPORARY',
                'internship' => 'INTERN',
                'volunteer' => 'VOLUNTEER',
                default => 'OTHER',
            },
            'identifier' => ['@type' => 'PropertyValue', 'name' => $org['name'], 'value' => $vacancy->reference_no],
            'hiringOrganization' => $this->clean([
                '@type' => 'Organization',
                'name' => $org['name'],
                'sameAs' => $this->canonical->url('/'),
                'logo' => $logo ? $this->canonical->absolute($logo) : null,
            ]),
            'jobLocation' => filled($vacancy->location) ? [
                '@type' => 'Place',
                'address' => $this->clean([
                    '@type' => 'PostalAddress',
                    'addressLocality' => $vacancy->location,
                    'addressCountry' => $org['country'],
                ]),
            ] : null,
            'url' => $url,
        ]);
    }

    /**
     * Only for question/answer pairs that are visibly rendered on the page.
     *
     * @param  list<array{question?: string, answer?: string}>  $items
     * @return array<string, mixed>|null
     */
    public function faqPage(string $url, array $items): ?array
    {
        $entities = collect($items)
            ->filter(static fn (array $item): bool => filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->map(fn (array $item): array => [
                '@type' => 'Question',
                'name' => $this->plain($item['question'], 300),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $this->plain($item['answer'], 2000)],
            ])
            ->values()
            ->all();

        return $entities === [] ? null : ['@type' => 'FAQPage', '@id' => $url.'#faq', 'mainEntity' => $entities];
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>|null
     */
    public function itemList(string $url, array $items): ?array
    {
        if ($items === []) {
            return null;
        }

        return [
            '@type' => 'ItemList',
            '@id' => $url.'#itemlist',
            'itemListElement' => collect($items)->values()->map(static fn (array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => $item['url'],
                'name' => $item['name'],
            ])->all(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    public function graph(array $nodes): string
    {
        return (string) json_encode(
            ['@context' => 'https://schema.org', '@graph' => $nodes],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    private function plain(?string $text, int $limit = 300): ?string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text)));

        return $text === '' ? null : Str::limit($text, $limit);
    }

    /**
     * Removes null and empty values so no empty properties are emitted.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function clean(array $node): array
    {
        return array_filter($node, static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }
}
