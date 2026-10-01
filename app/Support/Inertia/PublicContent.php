<?php

declare(strict_types=1);

namespace App\Support\Inertia;

use App\Models\Event;
use App\Models\Vacancy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Inertia\Response;

/**
 * Builds the props for the public collection, detail, event and vacancy
 * React pages from the same models the Blade views used.
 */
final class PublicContent
{
    /**
     * @param  array{eyebrow: string, title: string, items: LengthAwarePaginator, routePrefix: string, nameField: string, description?: string}  $data
     */
    public static function collection(array $data): Response
    {
        $locale = app()->getLocale();
        $items = $data['items'];
        $people = str_starts_with($data['routePrefix'], 'experts.');
        $count = $items->count();
        // A double-width lead card only when it leaves the three-column grid without gaps.
        $featureFirst = ! $people && $items->onFirstPage() && $count >= 5 && ($count - 2) % 3 === 0;

        return PublicPage::render('Public/Collection', null, [
            'meta' => ['title' => $data['title'], 'description' => $data['description'] ?? null],
            'breadcrumbs' => PublicPage::breadcrumbs([$data['title'] => null]),
            'header' => [
                'eyebrow' => $data['eyebrow'],
                'title' => $data['title'],
                'summary' => $data['description'] ?? null,
            ],
            'people' => $people,
            'items' => collect($items->items())->values()->map(function (Model $item, int $index) use ($data, $locale, $people, $items, $featureFirst): array {
                $photo = $people ? $item->expert?->profileMedia : null;
                $name = (string) data_get($item, $data['nameField']);
                $summary = data_get($item, 'summary')
                    ?? data_get($item, 'excerpt')
                    ?? data_get($item, 'description')
                    ?? data_get($item, 'overview')
                    ?? data_get($item, 'biography');
                $featured = $featureFirst && $index === 0;

                return [
                    'name' => $name,
                    'href' => route($data['routePrefix'], ['locale' => $locale, 'slug' => $item->slug]),
                    'number' => str_pad((string) ($items->firstItem() + $index), 2, '0', STR_PAD_LEFT),
                    'summary' => $summary ? Str::limit((string) $summary, $featured ? 260 : 170) : null,
                    'featured' => $featured,
                    'date' => isset($item->starts_at) ? $item->starts_at?->locale($locale)->translatedFormat('d M Y') : null,
                    'datetime' => isset($item->starts_at) ? $item->starts_at?->toAtomString() : null,
                    'title' => data_get($item, 'professional_title'),
                    'photo' => $photo?->isPubliclyUsable() ? $photo->publicUrl() : null,
                    'photoAlt' => $photo?->alt_text ?: $name,
                    'initial' => Str::upper(Str::substr($name, 0, 1)),
                ];
            })->all(),
            'pagination' => [
                'previous' => $items->onFirstPage() ? null : $items->previousPageUrl(),
                'next' => $items->hasMorePages() ? $items->nextPageUrl() : null,
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
                'emptyHref' => route('contact.create', ['locale' => $locale]),
            ],
        ]);
    }

    /** @param  array{item: Model, titleField: string}  $data */
    public static function detail(array $data): Response
    {
        $item = $data['item'];
        $locale = app()->getLocale();
        $type = class_basename($item);
        $title = (string) data_get($item, $data['titleField']);
        $typeLabel = match ($type) {
            'ServiceVersion' => __('Service'),
            'IndustryVersion' => __('Industry'),
            'ExpertVersion' => __('Expert'),
            'CaseStudyVersion' => __('Case study'),
            'InsightVersion' => __('Insight'),
            default => __(Str::headline(Str::replace('Version', '', $type))),
        };
        $indexRoute = match ($type) {
            'ServiceVersion' => 'services.index',
            'IndustryVersion' => 'industries.index',
            'ExpertVersion' => 'experts.index',
            'CaseStudyVersion' => 'case-studies.index',
            'InsightVersion' => 'insights.index',
            default => null,
        };
        $consultation = ['locale' => $locale];
        if ($type === 'ServiceVersion') {
            $consultation['service_id'] = $item->service_id;
        } elseif ($type === 'IndustryVersion') {
            $consultation['industry_id'] = $item->industry_id;
        }
        $summary = data_get($item, 'summary') ?? data_get($item, 'excerpt') ?? data_get($item, 'professional_title');
        $indexHref = $indexRoute ? route($indexRoute, ['locale' => $locale]) : null;
        $browseLabel = __('Browse all :type', ['type' => Str::lower($typeLabel)]);

        return PublicPage::render('Public/Detail', null, [
            'meta' => ['title' => $title, 'description' => $summary ? Str::limit((string) $summary, 160) : null],
            'breadcrumbs' => PublicPage::breadcrumbs([$typeLabel => $indexHref, $title => null]),
            'header' => ['eyebrow' => $typeLabel, 'title' => $title, 'summary' => $summary],
            'facts' => array_values(array_filter([
                ['label' => __('Content type'), 'value' => $typeLabel],
                filled(data_get($item, 'professional_title')) ? ['label' => __('Professional title'), 'value' => $item->professional_title] : null,
                is_array(data_get($item, 'languages')) && count($item->languages) ? ['label' => __('Languages'), 'value' => collect($item->languages)->join(', ')] : null,
                filled(data_get($item, 'updated_at')) ? [
                    'label' => __('Updated'),
                    'value' => $item->updated_at->locale($locale)->translatedFormat('d M Y'),
                    'datetime' => $item->updated_at->toAtomString(),
                ] : null,
            ])),
            'sections' => self::detailSections($item, $type),
            'actions' => [
                'consultation' => ['label' => __('Request advice'), 'href' => route('consultation.create', $consultation)],
                'index' => $indexHref ? ['label' => $browseLabel, 'href' => $indexHref] : null,
            ],
            'copy' => [
                'atAGlance' => __('At a glance'),
                'nextTitle' => __('Ready to move forward?'),
                'nextLead' => __('Tell us about the outcome you need. We will connect you with the relevant expertise.'),
            ],
        ], $title, $summary);
    }

    /** @return array<int, array<string, mixed>> */
    private static function detailSections(Model $item, string $type): array
    {
        $text = static fn (?string $eyebrow, string $heading, mixed $body): ?array => filled($body)
            ? ['kind' => 'text', 'eyebrow' => $eyebrow, 'heading' => $heading, 'body' => (string) $body]
            : null;
        $list = static fn (?string $eyebrow, string $heading, mixed $items): ?array => is_array($items) && count($items)
            ? ['kind' => 'list', 'eyebrow' => $eyebrow, 'heading' => $heading, 'items' => array_values($items)]
            : null;

        $sections = match ($type) {
            'ServiceVersion' => [
                $text(__('Client challenges'), __('The challenge this service addresses'), $item->problem_statement),
                $text(__('Approach'), __('How we work with you'), $item->approach),
                $list(__('Deliverables'), __('What the engagement can produce'), $item->deliverables),
                $text(__('Expected outcomes'), __('The value we work toward'), $item->benefits),
            ],
            'IndustryVersion' => [
                $text(__('Sector context'), __('Understanding the operating environment'), $item->overview),
                $text(__('Sector challenges'), __('Issues shaping decisions and delivery'), $item->challenges),
            ],
            'ExpertVersion' => (function () use ($item, $list): array {
                $photo = $item->expert?->profileMedia;

                return [
                    [
                        'kind' => 'portrait',
                        'photo' => $photo?->isPubliclyUsable() ? $photo->publicUrl() : null,
                        'photoAlt' => $photo?->alt_text ?: $item->display_name,
                        'initial' => Str::upper(Str::substr((string) $item->display_name, 0, 1)),
                        'body' => $item->biography,
                    ],
                    $list(__('Credentials'), __('Qualifications'), $item->qualifications),
                ];
            })(),
            'CaseStudyVersion' => collect([
                'challenge' => __('Challenge'),
                'approach' => __('Approach'),
                'outcomes' => __('Results and outcomes'),
            ])->values()->map(fn (string $heading, int $index) => $text(
                str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).' · '.__('Evidence'),
                $heading,
                data_get($item, ['challenge', 'approach', 'outcomes'][$index]),
            ))->all(),
            'InsightVersion' => [
                filled($item->body) ? ['kind' => 'prose', 'eyebrow' => __('Published insight'), 'body' => (string) $item->body] : null,
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
        $locale = app()->getLocale();
        $meta = trim(collect([
            $event->starts_at?->locale($locale)->translatedFormat('d M Y, H:i'),
            $event->timezone,
            $event->format ? __(ucfirst((string) $event->format)) : null,
        ])->filter()->join(' · '));

        return PublicPage::render('Public/Event', null, [
            'meta' => ['title' => $event->title, 'description' => Str::limit((string) $event->description, 160)],
            'breadcrumbs' => PublicPage::breadcrumbs([__('Events') => route('events.index', ['locale' => $locale]), $event->title => null]),
            'header' => ['eyebrow' => __('Event'), 'title' => $event->title, 'meta' => $meta],
            'event' => [
                'description' => $event->description,
                'venue' => $event->venue,
                'acceptsRegistrations' => $event->acceptsRegistrations(),
                'registrationUrl' => route('events.registrations.store', ['locale' => $locale, 'slug' => $event->slug]),
                'indexUrl' => route('events.index', ['locale' => $locale]),
            ],
            'copy' => [
                'about' => __('About this event'),
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
        ], $event->title, $event->description);
    }

    public static function vacancy(Vacancy $vacancy): Response
    {
        $locale = app()->getLocale();

        return PublicPage::render('Public/Vacancy', null, [
            'meta' => ['title' => $vacancy->title, 'description' => Str::limit((string) $vacancy->description, 160)],
            'breadcrumbs' => PublicPage::breadcrumbs([__('Careers') => route('careers.index', ['locale' => $locale]), $vacancy->title => null]),
            'header' => [
                'eyebrow' => __('Career opportunity').' · '.$vacancy->reference_no,
                'title' => $vacancy->title,
                'meta' => collect([$vacancy->location, $vacancy->type ? __(ucfirst((string) $vacancy->type)) : null])->filter()->join(' · '),
            ],
            'vacancy' => [
                'description' => $vacancy->description,
                'requirements' => $vacancy->requirements,
                'closesAt' => $vacancy->closes_at?->locale($locale)->translatedFormat('d M Y'),
                'closesAtIso' => $vacancy->closes_at?->toDateString(),
                'acceptsApplications' => $vacancy->acceptsApplications(),
                'applicationUrl' => route('careers.applications.store', ['locale' => $locale, 'slug' => $vacancy->slug]),
                'indexUrl' => route('careers.index', ['locale' => $locale]),
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
        ], $vacancy->title, $vacancy->description);
    }
}
