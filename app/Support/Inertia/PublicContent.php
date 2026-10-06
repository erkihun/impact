<?php

declare(strict_types=1);

namespace App\Support\Inertia;

use App\Enums\Seo\PublicResourceType;
use App\Enums\Seo\RobotsDirective;
use App\Models\CaseStudyVersion;
use App\Models\Event;
use App\Models\ExpertVersion;
use App\Models\InsightVersion;
use App\Models\MediaAsset;
use App\Models\SeoMetadata as SeoOverride;
use App\Models\ServiceVersion;
use App\Models\Vacancy;
use App\Services\Seo\InternalLinkService;
use App\Services\Seo\MediaImagePresenter;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Seo\SeoMetadataBuilder;
use App\Services\Seo\StructuredDataBuilder;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Response;

/**
 * Builds the props and SEO metadata for public collection, detail, event and
 * vacancy pages.
 */
final class PublicContent
{
    /** Query parameters that never change page content (tracking only). */
    private const TRACKING_PARAMETERS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'msclkid'];

    /**
     * @param  array{type: PublicResourceType, eyebrow: string, title: string, items: LengthAwarePaginator<int, Model>, description: string}  $data
     */
    public static function collection(array $data): Response
    {
        $type = $data['type'];
        $items = $data['items'];
        // Requests beyond the last page are not soft-404 listings.
        abort_if($items->currentPage() > 1 && $items->currentPage() > $items->lastPage(), 404);

        $people = $type === PublicResourceType::Expert;
        $count = $items->count();
        // A double-width lead card only when it leaves the three-column grid without gaps.
        $featureFirst = ! $people && $items->onFirstPage() && $count >= 5 && ($count - 2) % 3 === 0;
        $urls = app(PublicUrlGenerator::class);
        $images = app(MediaImagePresenter::class);
        $breadcrumbs = PublicPage::breadcrumbs([$data['title'] => null]);

        $cards = collect($items->items())->values()->map(function (Model $item, int $index) use ($type, $people, $items, $featureFirst, $urls, $images): array {
            $name = (string) data_get($item, $type->titleField());
            $summary = data_get($item, 'summary')
                ?? data_get($item, 'excerpt')
                ?? data_get($item, 'description')
                ?? data_get($item, 'overview')
                ?? data_get($item, 'biography');
            $featured = $featureFirst && $index === 0;
            $media = $people ? data_get($item, 'expert.profileMedia') : null;
            $photo = $media instanceof MediaAsset ? $images->attributes($media, '(min-width: 1024px) 22vw, (min-width: 640px) 45vw, 90vw', 'sm', $name) : null;

            return [
                'name' => $name,
                'href' => $urls->path($type, (string) $item->getAttribute('slug')),
                'number' => str_pad((string) ($items->firstItem() + $index), 2, '0', STR_PAD_LEFT),
                'summary' => $summary ? Str::limit(trim(strip_tags((string) $summary)), $featured ? 260 : 170) : null,
                'featured' => $featured,
                'date' => $item instanceof Event ? $item->starts_at->translatedFormat('d M Y') : null,
                'datetime' => $item instanceof Event ? $item->starts_at->toAtomString() : null,
                'title' => data_get($item, 'professional_title'),
                'photo' => $photo['src'] ?? null,
                'photoSrcset' => $photo['srcset'] ?? null,
                'photoSizes' => $photo['sizes'] ?? null,
                'photoWidth' => $photo['width'] ?? null,
                'photoHeight' => $photo['height'] ?? null,
                'photoAlt' => $photo['alt'] ?? $name,
                'initial' => Str::upper(Str::substr($name, 0, 1)),
            ];
        })->all();

        $seo = SeoMetadataBuilder::make()
            ->subject('page', $type->indexRoute())
            ->title($data['title'])
            ->description($data['description'])
            ->type('website', 'CollectionPage')
            ->robots(self::hasTransientQuery() ? RobotsDirective::NoindexFollow : RobotsDirective::IndexFollow)
            ->listable($items->onFirstPage());
        $seo->structuredData(app(StructuredDataBuilder::class)->itemList(
            $seo->canonicalUrl(),
            collect($items->items())->map(static fn (Model $item): array => [
                'name' => (string) data_get($item, $type->titleField()),
                'url' => $urls->url($type, (string) $item->getAttribute('slug')),
            ])->values()->all(),
        ));

        return PublicPage::render('Public/Collection', $type->indexRoute(), [
            'breadcrumbs' => $breadcrumbs,
            'header' => [
                'eyebrow' => $data['eyebrow'],
                'title' => $data['title'],
                'summary' => $data['description'],
            ],
            'people' => $people,
            'items' => $cards,
            'pagination' => [
                'previous' => $items->onFirstPage() ? null : self::pageUrl($items, $items->currentPage() - 1),
                'next' => $items->hasMorePages() ? self::pageUrl($items, $items->currentPage() + 1) : null,
            ],
            'copy' => [
                'learnMore' => __('Learn more'),
                'viewProfile' => __('View profile'),
                'previous' => __('Previous'),
                'next' => __('Next'),
                'pagination' => __('Pagination'),
                'emptyTitle' => __('No records are available yet.'),
                'emptyDescription' => __('Contact our team if you need help finding the right information.'),
                'emptyAction' => __('Choose a contact route'),
                'emptyHref' => route('contact.create'),
            ],
        ], seo: $seo);
    }

    public static function detail(PublicResourceType $type, Model $item): Response
    {
        $title = (string) data_get($item, $type->titleField());
        $typeLabel = __($type->label());
        $indexHref = route($type->indexRoute());
        $consultation = match ($type) {
            PublicResourceType::Service => ['service_id' => data_get($item, 'service_id')],
            PublicResourceType::Industry => ['industry_id' => data_get($item, 'industry_id')],
            default => [],
        };
        $summary = data_get($item, 'summary') ?? data_get($item, 'excerpt') ?? data_get($item, 'professional_title');
        $links = app(InternalLinkService::class);
        $related = $links->relatedFor($type, $item);
        $breadcrumbs = PublicPage::breadcrumbs([__($type->pluralLabel()) => $indexHref, $title => null]);

        return PublicPage::render('Public/Detail', null, [
            'breadcrumbs' => $breadcrumbs,
            'header' => ['eyebrow' => $typeLabel, 'title' => $title, 'summary' => $summary],
            'facts' => array_values(array_filter([
                ['label' => __('Content type'), 'value' => $typeLabel],
                filled(data_get($item, 'professional_title')) ? ['label' => __('Professional title'), 'value' => data_get($item, 'professional_title')] : null,
                is_array(data_get($item, 'languages')) && data_get($item, 'languages') !== [] ? ['label' => __('Languages'), 'value' => collect(data_get($item, 'languages'))->join(', ')] : null,
                ($updated = data_get($item, 'updated_at')) instanceof CarbonInterface ? [
                    'label' => __('Updated'),
                    'value' => $updated->translatedFormat('d M Y'),
                    'datetime' => $updated->toAtomString(),
                ] : null,
            ])),
            'sections' => self::detailSections($item, $type),
            'related' => $related,
            'actions' => [
                'consultation' => ['label' => __('Request advice'), 'href' => route('consultation.create', $consultation)],
                'index' => ['label' => __('Browse all :type', ['type' => Str::lower(__($type->pluralLabel()))]), 'href' => $indexHref],
            ],
            'copy' => [
                'atAGlance' => __('At a glance'),
                'related' => __('Related content'),
                'nextTitle' => __('Ready to move forward?'),
                'nextLead' => __('Tell us about the outcome you need. We will connect you with the relevant expertise.'),
            ],
        ], $title, $summary, self::detailSeo($type, $item, $title, $related));
    }

    /**
     * @param  list<array{type: string, heading: string, items: list<array{name: string, href: string, summary: string|null}>}>  $related
     */
    private static function detailSeo(PublicResourceType $type, Model $item, string $title, array $related): SeoMetadataBuilder
    {
        $urls = app(PublicUrlGenerator::class);
        $schema = app(StructuredDataBuilder::class);
        $images = app(MediaImagePresenter::class);
        $url = (string) $urls->urlFor($item);
        $identity = app(InternalLinkService::class)->identity($type, $item);
        $override = SeoOverride::query()->where('subject_type', $type->value)->where('subject_key', $identity)->first();
        $seo = SeoMetadataBuilder::make()
            ->subject($type->value, $identity)
            ->canonical($url)
            ->meaningfulQuery([])
            ->listable();

        $relatedNames = static fn (string $relatedType): array => collect($related)
            ->firstWhere('type', $relatedType)['items'] ?? [];

        match (true) {
            $item instanceof ServiceVersion => $seo
                ->title(self::consultingTitle($title))
                ->description($item->summary, $item->problem_statement, $item->approach)
                ->mainEntity($url.'#service')
                ->structuredData($schema->service($item, $url, array_values(array_filter((array) ($override->geographic_relevance ?? []), 'is_string')))),
            $type === PublicResourceType::Industry => $seo
                ->title(self::consultingTitle($title))
                ->description(data_get($item, 'summary'), data_get($item, 'overview'), data_get($item, 'challenges')),
            $item instanceof ExpertVersion => (function () use ($seo, $item, $title, $url, $schema, $images, $relatedNames): void {
                $image = $images->seoImage($item->expert->profileMedia ?? null, $title);
                $seo->title(self::expertTitle($title, (string) $item->professional_title))
                    ->description($item->biography, $item->professional_title)
                    ->type('profile', 'ProfilePage')
                    ->image($image)
                    ->mainEntity($url.'#person')
                    ->structuredData($schema->person($item, $url, $image, array_column($relatedNames(PublicResourceType::Service->value), 'name')));
            })(),
            $item instanceof CaseStudyVersion => (function () use ($seo, $item, $title, $url, $schema): void {
                $seo->title($title)
                    ->description($item->outcomes, $item->challenge, $item->approach)
                    ->type('article')
                    ->dates($item->created_at?->toImmutable(), $item->updated_at?->toImmutable())
                    ->structuredData($schema->article($item, $url, null, $item->created_at?->toImmutable(), $item->updated_at?->toImmutable()));
            })(),
            $item instanceof InsightVersion => (function () use ($seo, $item, $title, $url, $schema, $images): void {
                $image = $images->seoImage($item->insight->primaryMedia ?? null, $title);
                $published = $item->insight->published_at ?? $item->created_at?->toImmutable();
                $seo->title($title)
                    ->description($item->excerpt, $item->body)
                    ->type('article')
                    ->image($image)
                    ->dates($published, $item->updated_at?->toImmutable())
                    ->structuredData($schema->article($item, $url, $image, $published, $item->updated_at?->toImmutable()));
            })(),
            default => $seo->title($title),
        };

        return $seo;
    }

    /** "Digital transformation consulting", unless the name already says so. */
    private static function consultingTitle(string $name): string
    {
        if (Str::contains($name, ['consult', 'Consult'])) {
            return $name;
        }

        return $name.(preg_match('/\s[a-z]/', $name) === 1 ? ' consulting' : ' Consulting');
    }

    /** "Name | Specialization" when it fits a search result, otherwise the name. */
    private static function expertTitle(string $name, string $specialization): string
    {
        $candidate = trim($name.' | '.$specialization, ' |');

        return mb_strlen($candidate) <= 48 ? $candidate : $name;
    }

    /** @return array<int, array<string, mixed>> */
    private static function detailSections(Model $item, PublicResourceType $type): array
    {
        $text = static fn (?string $eyebrow, string $heading, mixed $body): ?array => filled($body)
            ? ['kind' => 'text', 'eyebrow' => $eyebrow, 'heading' => $heading, 'body' => (string) $body]
            : null;
        $list = static fn (?string $eyebrow, string $heading, mixed $items): ?array => is_array($items) && count($items)
            ? ['kind' => 'list', 'eyebrow' => $eyebrow, 'heading' => $heading, 'items' => array_values($items)]
            : null;

        $sections = match ($type) {
            PublicResourceType::Service => [
                $text(__('Client challenges'), __('The challenge this service addresses'), data_get($item, 'problem_statement')),
                $text(__('Approach'), __('How we work with you'), data_get($item, 'approach')),
                $list(__('Deliverables'), __('What the engagement can produce'), data_get($item, 'deliverables')),
                $text(__('Expected outcomes'), __('The value we work toward'), data_get($item, 'benefits')),
            ],
            PublicResourceType::Industry => [
                $text(__('Sector context'), __('Understanding the operating environment'), data_get($item, 'overview')),
                $text(__('Sector challenges'), __('Issues shaping decisions and delivery'), data_get($item, 'challenges')),
            ],
            PublicResourceType::Expert => (function () use ($item, $list): array {
                $name = (string) data_get($item, 'display_name');
                $media = data_get($item, 'expert.profileMedia');
                $photo = $media instanceof MediaAsset ? app(MediaImagePresenter::class)->attributes($media, '(min-width: 1024px) 28rem, 90vw', 'md', $name) : null;

                return [
                    [
                        'kind' => 'portrait',
                        'photo' => $photo['src'] ?? null,
                        'photoSrcset' => $photo['srcset'] ?? null,
                        'photoSizes' => $photo['sizes'] ?? null,
                        'photoWidth' => $photo['width'] ?? null,
                        'photoHeight' => $photo['height'] ?? null,
                        'photoAlt' => $photo['alt'] ?? $name,
                        'initial' => Str::upper(Str::substr($name, 0, 1)),
                        'body' => data_get($item, 'biography'),
                    ],
                    $list(__('Credentials'), __('Qualifications'), data_get($item, 'qualifications')),
                ];
            })(),
            PublicResourceType::CaseStudy => collect([
                'challenge' => __('Challenge'),
                'approach' => __('Approach'),
                'outcomes' => __('Results and outcomes'),
            ])->values()->map(fn (string $heading, int $index) => $text(
                str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).' · '.__('Evidence'),
                $heading,
                data_get($item, ['challenge', 'approach', 'outcomes'][$index]),
            ))->all(),
            PublicResourceType::Insight => [
                filled(data_get($item, 'body')) ? ['kind' => 'prose', 'eyebrow' => __('Published insight'), 'body' => (string) data_get($item, 'body')] : null,
            ],
            default => [
                $text(null, __('Overview'), data_get($item, 'description') ?? data_get($item, 'overview')),
                $text(null, __('Details'), data_get($item, 'body')),
            ],
        };

        return array_values(array_filter($sections));
    }

    public static function event(Event $event): Response
    {
        $meta = trim(collect([
            $event->starts_at->setTimezone((string) ($event->timezone ?: 'UTC'))->translatedFormat('d M Y, H:i'),
            $event->timezone,
            $event->format ? __(ucfirst((string) $event->format)) : null,
        ])->filter()->join(' · '));
        $url = (string) app(PublicUrlGenerator::class)->urlFor($event);
        $related = app(InternalLinkService::class)->relatedFor(PublicResourceType::Event, $event);

        $seo = SeoMetadataBuilder::make()
            ->subject(PublicResourceType::Event->value, (string) $event->getKey())
            ->canonical($url)
            ->meaningfulQuery([])
            ->listable()
            ->title($event->title)
            ->description($event->description)
            ->mainEntity($url.'#event')
            ->structuredData(app(StructuredDataBuilder::class)->event($event, $url, null));

        return PublicPage::render('Public/Event', null, [
            'breadcrumbs' => PublicPage::breadcrumbs([__('Events') => route('events.index'), $event->title => null]),
            'header' => ['eyebrow' => __('Event'), 'title' => $event->title, 'meta' => $meta],
            'related' => $related,
            'event' => [
                'description' => $event->description,
                'venue' => $event->venue,
                'acceptsRegistrations' => $event->acceptsRegistrations(),
                'registrationUrl' => route('events.registrations.store', ['slug' => $event->slug]),
                'indexUrl' => route('events.index'),
            ],
            'copy' => [
                'about' => __('About this event'),
                'related' => __('Related content'),
                'venue' => __('Venue'),
                'attendance' => __('Attendance'),
                'register' => __('Register'),
                'registerNote' => __('Registration is confirmed only after the confirmation page displays a reference.'),
                'fullName' => __('Full name'),
                'email' => __('Email address'),
                'required' => __('required'),
                'privacy' => __('I agree to the use of my details to administer this registration.'),
                'marketing' => __('Send me relevant future insights and events.'),
                'submit' => __('Confirm registration'),
                'unavailable' => __('Registration unavailable'),
                'closed' => __('Registration closed'),
                'closedNote' => __('This event is full or no longer accepting registrations.'),
                'upcoming' => __('View upcoming events'),
                'errorSummary' => __('Please correct the following fields before continuing.'),
            ],
        ], $event->title, $event->description, $seo);
    }

    public static function vacancy(Vacancy $vacancy): Response
    {
        $url = (string) app(PublicUrlGenerator::class)->urlFor($vacancy);
        $seo = SeoMetadataBuilder::make()
            ->subject(PublicResourceType::Vacancy->value, (string) $vacancy->getKey())
            ->canonical($url)
            ->meaningfulQuery([])
            ->listable()
            ->title($vacancy->title.' – '.__('Careers'))
            ->description($vacancy->description, $vacancy->requirements)
            ->structuredData(app(StructuredDataBuilder::class)->jobPosting($vacancy, $url));

        return PublicPage::render('Public/Vacancy', null, [
            'breadcrumbs' => PublicPage::breadcrumbs([__('Careers') => route('careers.index'), $vacancy->title => null]),
            'header' => [
                'eyebrow' => __('Career opportunity').' · '.$vacancy->reference_no,
                'title' => $vacancy->title,
                'meta' => collect([$vacancy->location, $vacancy->type ? __(ucfirst(str_replace('_', ' ', (string) $vacancy->type))) : null])->filter()->join(' · '),
            ],
            'vacancy' => [
                'description' => $vacancy->description,
                'requirements' => $vacancy->requirements,
                'closesAt' => $vacancy->closes_at?->translatedFormat('d M Y'),
                'closesAtIso' => $vacancy->closes_at?->toDateString(),
                'acceptsApplications' => $vacancy->acceptsApplications(),
                'applicationUrl' => route('careers.applications.store', ['slug' => $vacancy->slug]),
                'indexUrl' => route('careers.index'),
            ],
            'copy' => [
                'opportunity' => __('The opportunity'),
                'requirements' => __('Requirements'),
                'closingDate' => __('Closing date'),
                'secure' => __('Secure application'),
                'apply' => __('Apply'),
                'applyNote' => __('An application is received only when the confirmation page displays a reference.'),
                'fullName' => __('Full name'),
                'email' => __('Email address'),
                'phone' => __('Phone'),
                'required' => __('required'),
                'cv' => __('CV (PDF or DOCX, max 10 MB)'),
                'cvHelp' => __('The file remains unavailable to staff until security processing is complete.'),
                'coverLetter' => __('Cover letter'),
                'privacy' => __('I agree to the processing of my application under the recruitment privacy notice.'),
                'submit' => __('Submit application'),
                'unavailable' => __('Application unavailable'),
                'closed' => __('Applications closed'),
                'closedNote' => __('This opportunity is no longer accepting applications.'),
                'current' => __('View current opportunities'),
                'errorSummary' => __('Please correct the following fields before continuing.'),
            ],
        ], $vacancy->title, $vacancy->description, $seo);
    }

    /** Query state other than pagination and tracking makes a listing transient. */
    public static function hasTransientQuery(): bool
    {
        return collect(request()->query())
            ->keys()
            ->reject(static fn (string $key): bool => $key === 'page' || in_array($key, self::TRACKING_PARAMETERS, true))
            ->isNotEmpty();
    }

    /**
     * Stable pagination URLs: page 1 is the bare listing URL.
     *
     * @param  LengthAwarePaginator<int, Model>  $items
     */
    private static function pageUrl(LengthAwarePaginator $items, int $page): string
    {
        $path = (string) parse_url($items->path() ?? request()->url(), PHP_URL_PATH);

        return $page <= 1 ? $path : $path.'?page='.$page;
    }
}
