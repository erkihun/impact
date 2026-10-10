<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Seo\PublicResourceType;
use App\Models\CaseStudy;
use App\Models\CaseStudyVersion;
use App\Models\Event;
use App\Models\Industry;
use App\Models\IndustryVersion;
use App\Models\Insight;
use App\Models\InsightVersion;
use App\Models\Service;
use App\Models\ServiceVersion;
use App\Models\User;
use App\Models\Vacancy;
use App\Queries\Seo\PublicResourceQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Editor-facing map of the public resource families the website renders:
 * who may manage each one, which fields an editor fills in, and which model
 * stores each field.
 *
 * Services, industries, case studies and insights keep their text on an
 * English version row (`on: version`) and their status on the parent
 * record; events and vacancies are single records. Experts keep their own
 * dedicated module.
 */
final class WebsiteCollections
{
    private const VERSIONED_STATUSES = [
        ['value' => 'draft', 'label' => 'Draft', 'help' => 'Not visible on the website.'],
        ['value' => 'published', 'label' => 'Published', 'help' => 'Visible on the website. Saving updates the live page immediately.', 'live' => true],
        ['value' => 'unpublished', 'label' => 'Unpublished', 'help' => 'Temporarily hidden from the website. You can publish it again later.'],
        ['value' => 'archived', 'label' => 'Archived', 'help' => 'Retired. Its web address tells visitors and search engines it is gone.'],
    ];

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'services' => [
                'label' => 'Services',
                'singular' => 'service',
                'description' => 'Consulting services listed on the Services page.',
                'icon' => 'services',
                'permission' => 'services.manage',
                'resource' => PublicResourceType::Service,
                'record' => Service::class,
                'version' => ServiceVersion::class,
                'title' => 'name',
                'summary' => 'summary',
                'statuses' => self::VERSIONED_STATUSES,
                'fields' => [
                    self::field('name', 'Service name', 'text', required: true, max: 160),
                    self::field('summary', 'Short summary', 'textarea', max: 2000, help: 'Shown in the services list and in search results.'),
                    self::field('problem_statement', 'The challenge this service addresses', 'textarea', max: 20000),
                    self::field('approach', 'How we work', 'textarea', max: 100000),
                    self::field('deliverables', 'Deliverables', 'lines', help: 'One deliverable per line.'),
                    self::field('benefits', 'Expected outcomes', 'textarea', max: 20000),
                ],
            ],
            'industries' => [
                'label' => 'Industries',
                'singular' => 'industry',
                'description' => 'Sectors listed on the Industries page.',
                'icon' => 'industries',
                'permission' => 'industries.manage',
                'resource' => PublicResourceType::Industry,
                'record' => Industry::class,
                'version' => IndustryVersion::class,
                'title' => 'name',
                'summary' => 'summary',
                'statuses' => self::VERSIONED_STATUSES,
                'fields' => [
                    self::field('name', 'Industry name', 'text', required: true, max: 160),
                    self::field('summary', 'Short summary', 'textarea', max: 2000, help: 'Shown in the industries list and in search results.'),
                    self::field('overview', 'Sector context', 'textarea', max: 100000),
                    self::field('challenges', 'Sector challenges', 'textarea', max: 100000),
                ],
            ],
            'case-studies' => [
                'label' => 'Case studies',
                'singular' => 'case study',
                'description' => 'Client work shown on the Case studies page.',
                'icon' => 'case-studies',
                'permission' => 'case-studies.manage',
                'resource' => PublicResourceType::CaseStudy,
                'record' => CaseStudy::class,
                'version' => CaseStudyVersion::class,
                'title' => 'title',
                'summary' => 'outcomes',
                'statuses' => self::VERSIONED_STATUSES,
                'fields' => [
                    self::field('title', 'Title', 'text', required: true, max: 220),
                    self::field('challenge', 'Challenge', 'textarea', required: true, max: 100000),
                    self::field('approach', 'Approach', 'textarea', required: true, max: 100000),
                    self::field('outcomes', 'Results and outcomes', 'textarea', required: true, max: 100000, help: 'Also used as the summary in lists and search results.'),
                    self::field('client_display_mode', 'How the client is shown', 'select', on: 'record', required: true, group: 'settings', options: [
                        ['value' => 'anonymized', 'label' => 'Anonymized (client not named)'],
                        ['value' => 'named', 'label' => 'Named (needs client consent)'],
                        ['value' => 'confidential', 'label' => 'Confidential (never published)'],
                    ]),
                    self::field('client_consent_at', 'Client consent date', 'datetime', on: 'record', group: 'settings', help: 'Required before a named case study can be published.'),
                    self::field('authorization_reference', 'Consent reference', 'text', on: 'record', max: 255, group: 'settings', help: 'Optional. For example, a letter or email reference.'),
                ],
                'rules' => static fn (array $input): array => ($input['status'] ?? null) !== 'published' ? [] : [
                    'client_display_mode' => [Rule::notIn(['confidential'])],
                    'client_consent_at' => [Rule::requiredIf(($input['client_display_mode'] ?? null) === 'named')],
                ],
                'messages' => [
                    'client_display_mode.not_in' => 'Confidential case studies cannot be published. Choose anonymized or named.',
                    'client_consent_at.required' => 'Record when the client agreed to be named before publishing.',
                ],
            ],
            'insights' => [
                'label' => 'Insights',
                'singular' => 'insight',
                'description' => 'Articles and publications on the Insights page.',
                'icon' => 'insights',
                'permission' => 'insights.manage',
                'resource' => PublicResourceType::Insight,
                'record' => Insight::class,
                'version' => InsightVersion::class,
                'title' => 'title',
                'summary' => 'excerpt',
                'statuses' => self::VERSIONED_STATUSES,
                'fields' => [
                    self::field('title', 'Title', 'text', required: true, max: 220),
                    self::field('excerpt', 'Excerpt', 'textarea', required: true, max: 2000, help: 'One or two sentences shown in lists and search results.'),
                    self::field('body', 'Article text', 'textarea', max: 100000),
                    self::field('primary_media_id', 'Sharing image', 'media', on: 'record', group: 'settings', help: 'Shown when the insight is shared on social media. Upload images in the media library first.'),
                    self::field('expires_at', 'Hide after', 'datetime', on: 'record', group: 'settings', help: 'Optional. The insight leaves the website after this date.'),
                ],
            ],
            'events' => [
                'label' => 'Events',
                'singular' => 'event',
                'description' => 'Events and registrations on the Events page.',
                'icon' => 'events',
                'permission' => 'events.manage',
                'resource' => PublicResourceType::Event,
                'record' => Event::class,
                'version' => null,
                'title' => 'title',
                'summary' => 'description',
                'statuses' => [
                    ['value' => 'draft', 'label' => 'Draft', 'help' => 'Not visible on the website.'],
                    ['value' => 'published', 'label' => 'Published', 'help' => 'Visible on the website. Registration is not open yet.', 'live' => true],
                    ['value' => 'registration_open', 'label' => 'Registration open', 'help' => 'Visible and accepting registrations until the closing time or capacity is reached.', 'live' => true],
                    ['value' => 'registration_closed', 'label' => 'Registration closed', 'help' => 'Visible, but no longer accepting registrations.', 'live' => true],
                    ['value' => 'completed', 'label' => 'Completed', 'help' => 'The event has taken place. Its page stays available.', 'live' => true],
                    ['value' => 'cancelled', 'label' => 'Cancelled', 'help' => 'Removed from the website. Its web address tells visitors it is gone.'],
                ],
                'fields' => [
                    self::field('title', 'Event title', 'text', required: true, max: 220),
                    self::field('description', 'Description', 'textarea', required: true, max: 100000),
                    self::field('format', 'Format', 'select', required: true, options: [
                        ['value' => 'physical', 'label' => 'In person'],
                        ['value' => 'online', 'label' => 'Online'],
                        ['value' => 'hybrid', 'label' => 'Hybrid'],
                    ]),
                    self::field('venue', 'Venue', 'text', max: 255),
                    self::field('starts_at', 'Starts', 'datetime', required: true, timezoneField: 'timezone'),
                    self::field('ends_at', 'Ends', 'datetime', required: true, timezoneField: 'timezone'),
                    self::field('timezone', 'Timezone', 'text', required: true, max: 64, help: 'Start and end times are in this timezone, for example Africa/Addis_Ababa.'),
                    self::field('capacity', 'Capacity', 'number', group: 'settings', help: 'Optional. Registration closes when this many people have registered.'),
                    self::field('registration_closes_at', 'Registration closes', 'datetime', group: 'settings', timezoneField: 'timezone'),
                ],
                'rules' => static fn (array $input): array => [
                    'timezone' => ['timezone:all'],
                    'ends_at' => ['after:starts_at'],
                ],
                'messages' => ['ends_at.after' => 'The event must end after it starts.'],
            ],
            'vacancies' => [
                'label' => 'Vacancies',
                'singular' => 'vacancy',
                'description' => 'Job openings on the Careers page.',
                'icon' => 'careers',
                'permission' => 'vacancies.manage',
                'resource' => PublicResourceType::Vacancy,
                'record' => Vacancy::class,
                'version' => null,
                'title' => 'title',
                'summary' => 'description',
                'statuses' => [
                    ['value' => 'draft', 'label' => 'Draft', 'help' => 'Not visible on the website.'],
                    ['value' => 'published', 'label' => 'Published', 'help' => 'Visible on the website and accepting applications between the opening and closing dates.', 'live' => true],
                    ['value' => 'closed', 'label' => 'Closed', 'help' => 'No longer accepting applications. Removed from the website.'],
                    ['value' => 'archived', 'label' => 'Archived', 'help' => 'Retired and removed from the website.'],
                ],
                'fields' => [
                    self::field('title', 'Job title', 'text', required: true, max: 220),
                    self::field('type', 'Employment type', 'select', required: true, options: [
                        ['value' => 'full_time', 'label' => 'Full time'],
                        ['value' => 'part_time', 'label' => 'Part time'],
                        ['value' => 'contract', 'label' => 'Contract'],
                        ['value' => 'consultancy', 'label' => 'Consultancy'],
                        ['value' => 'internship', 'label' => 'Internship'],
                    ]),
                    self::field('location', 'Location', 'text', max: 160),
                    self::field('description', 'Role description', 'textarea', required: true, max: 100000),
                    self::field('requirements', 'Requirements', 'textarea', required: true, max: 100000),
                    self::field('opens_at', 'Applications open', 'datetime', group: 'settings', help: 'Optional. Leave blank to accept applications as soon as it is published.'),
                    self::field('closes_at', 'Applications close', 'datetime', group: 'settings'),
                    self::field('application_limit', 'Maximum applications', 'number', group: 'settings', help: 'Optional. Applications stop when this number is reached.'),
                ],
                'rules' => static fn (array $input): array => filled($input['opens_at'] ?? null) ? ['closes_at' => ['after:opens_at']] : [],
                'messages' => ['closes_at.after' => 'Applications must close after they open.'],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function find(string $key): ?array
    {
        $definition = self::all()[$key] ?? null;

        return $definition === null ? null : [...$definition, 'key' => $key];
    }

    /** @return array<string, array<string, mixed>> */
    public static function permittedFor(User $user): array
    {
        return array_filter(
            self::all(),
            static fn (array $definition): bool => $user->hasPermission($definition['permission']),
        );
    }

    /** @return list<string> */
    public static function permissions(): array
    {
        return array_values(array_map(static fn (array $definition): string => $definition['permission'], self::all()));
    }

    /** @return list<string> */
    public static function statusValues(array $definition): array
    {
        return array_column($definition['statuses'], 'value');
    }

    /** @return list<string> statuses that make a record visible to visitors */
    public static function liveStatusValues(array $definition): array
    {
        return array_column(array_filter($definition['statuses'], static fn (array $status): bool => $status['live'] ?? false), 'value');
    }

    /**
     * The row holding an item's editable text and slug: the latest English
     * version of a versioned resource, or the record itself.
     *
     * @param  array<string, mixed>  $definition
     */
    public static function editableRow(array $definition, ?Model $record): ?Model
    {
        if ($record === null || $definition['version'] === null) {
            return $record;
        }

        return $definition['version']::query()
            ->where($definition['resource']->parentKey(), $record->getKey())
            ->where('locale', PublicResourceQuery::SITE_LOCALE)
            ->orderByDesc('version_no')
            ->first();
    }

    /**
     * The definition without server-only keys, for the editor screens.
     *
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    public static function present(array $definition): array
    {
        $type = $definition['resource'];

        return [
            'key' => $definition['key'],
            'label' => $definition['label'],
            'singular' => $definition['singular'],
            'description' => $definition['description'],
            'icon' => $definition['icon'],
            'title' => $definition['title'],
            'summary' => $definition['summary'],
            'statuses' => $definition['statuses'],
            'fields' => $definition['fields'],
            'publicPath' => '/'.$type->segment(),
        ];
    }

    /**
     * @param  list<array{value: string, label: string}>  $options
     * @return array<string, mixed>
     */
    private static function field(
        string $name,
        string $label,
        string $type,
        string $on = 'version',
        bool $required = false,
        ?int $max = null,
        string $group = 'content',
        ?string $help = null,
        array $options = [],
        ?string $timezoneField = null,
    ): array {
        return array_filter([
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'on' => $on,
            'required' => $required,
            'max' => $max,
            'group' => $group,
            'help' => $help,
            'options' => $options,
            'timezoneField' => $timezoneField,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
