<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Content\SaveWebsiteContentAction;
use App\Enums\MediaStatus;
use App\Enums\MediaVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveWebsiteContentRequest;
use App\Models\AuditEvent;
use App\Models\Event;
use App\Models\Expert;
use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Vacancy;
use App\Queries\Seo\PublicResourceQuery;
use App\Services\Seo\PublicUrlGenerator;
use App\Support\AuditEventPresenter;
use App\Support\CorrelationContext;
use App\Support\Inertia\WorkspacePage;
use App\Support\Settings\EffectiveSettings;
use App\Support\WebsiteCollections;
use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Response as View;

/**
 * One place to manage everything visitors browse on the website: services,
 * industries, case studies, insights, events and vacancies.
 */
final class WebsiteContentController extends Controller
{
    /** Retired items are listed only when their status is chosen. */
    private const RETIRED_STATUSES = ['archived', 'cancelled'];

    public function __construct(
        private readonly PublicResourceQuery $resources,
        private readonly PublicUrlGenerator $urls,
    ) {}

    public function overview(Request $request): View
    {
        $user = $this->user($request);
        $collections = WebsiteCollections::permittedFor($user);
        $experts = $user->hasPermission('experts.manage');
        abort_if($collections === [] && ! $experts, 403);

        return WorkspacePage::render('admin.website.index', [
            'collections' => collect($collections)->map(function (array $definition, string $key): array {
                $counts = $this->statusCounts($definition);

                return [
                    ...WebsiteCollections::present([...$definition, 'key' => $key]),
                    'total' => $counts->sum(),
                    'live' => $counts->only(WebsiteCollections::liveStatusValues($definition))->sum(),
                    'drafts' => (int) $counts->get('draft', 0),
                ];
            })->values()->all(),
            'experts' => $experts ? [
                'total' => Expert::query()->count(),
                'live' => Expert::query()->where('status', 'published')->count(),
                'drafts' => Expert::query()->where('status', 'draft')->count(),
            ] : null,
        ]);
    }

    public function index(Request $request, string $collection, EffectiveSettings $settings): View
    {
        $definition = $this->definition($request, $collection);
        $type = $definition['resource'];
        $versioned = $definition['version'] !== null;
        $titleColumn = $definition['title'];
        $search = Str::limit(trim((string) $request->query('q', '')), 100, '');
        $status = (string) $request->query('status', '');
        $like = '%'.addcslashes($search, '%_\\').'%';

        $records = $definition['record']::query()
            ->when($versioned, fn ($query) => $query->with(['versions' => fn ($versions) => $versions
                ->where('locale', PublicResourceQuery::SITE_LOCALE)
                ->orderByDesc('version_no')]))
            ->when($search !== '', fn ($query) => $versioned
                ? $query->whereHas('versions', fn ($versions) => $versions
                    ->where('locale', PublicResourceQuery::SITE_LOCALE)
                    ->where($titleColumn, 'like', $like))
                : $query->where($titleColumn, 'like', $like))
            ->when(
                in_array($status, WebsiteCollections::statusValues($definition), true),
                fn ($query) => $query->where('status', $status),
                fn ($query) => $query->whereNotIn('status', self::RETIRED_STATUSES),
            )
            ->latest('updated_at')
            ->paginate(min(
                $settings->integer('content.default_items_per_page'),
                $settings->integer('performance.maximum_pagination_size'),
            ))
            ->withQueryString();

        $liveSlugs = $this->resources->visible($type)
            ->whereIn($type->parentKey(), collect($records->items())->map(fn (Model $record): string => (string) $record->getKey()))
            ->when($versioned, fn ($query) => $query->orderBy('version_no'))
            ->pluck('slug', $type->parentKey());

        $records->through(function (Model $record) use ($definition, $versioned, $liveSlugs, $type): array {
            $row = $versioned ? $record->getRelation('versions')->first() : $record;
            $id = (string) $record->getKey();

            return [
                'id' => $id,
                'title' => $row?->getAttribute($definition['title']),
                'slug' => $row?->getAttribute('slug'),
                'status' => (string) $record->getRawOriginal('status'),
                'meta' => $this->listMeta($record),
                'updatedAt' => collect([$record->getAttribute('updated_at'), $row?->getAttribute('updated_at')])->filter()->max()?->toAtomString(),
                'liveUrl' => isset($liveSlugs[$id]) ? $this->urls->path($type, (string) $liveSlugs[$id]) : null,
            ];
        });

        return WorkspacePage::render('admin.website.collection', [
            'collection' => WebsiteCollections::present($definition),
            'collections' => $this->navigation($request),
            'items' => $records,
            'counts' => $this->statusCounts($definition)->all(),
            'filters' => ['q' => $search, 'status' => $status],
        ]);
    }

    public function create(Request $request, string $collection, EffectiveSettings $settings): View
    {
        return $this->form($request, $this->definition($request, $collection), null, $settings);
    }

    public function store(
        SaveWebsiteContentRequest $request,
        SaveWebsiteContentAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $definition = $request->definition();
        $record = $action->execute($this->user($request), $definition, null, $request->validated(), $correlation->id());

        return $this->saved($definition, $record);
    }

    public function edit(Request $request, string $collection, string $record, EffectiveSettings $settings): View
    {
        $definition = $this->definition($request, $collection);

        return $this->form($request, $definition, $definition['record']::query()->findOrFail($record), $settings);
    }

    public function update(
        SaveWebsiteContentRequest $request,
        SaveWebsiteContentAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $definition = $request->definition();
        $record = $action->execute($this->user($request), $definition, $request->record(), $request->validated(), $correlation->id());

        return $this->saved($definition, $record);
    }

    /** @param  array<string, mixed>  $definition */
    private function form(Request $request, array $definition, ?Model $record, EffectiveSettings $settings): View
    {
        $type = $definition['resource'];
        $row = WebsiteCollections::editableRow($definition, $record);
        $current = $record === null ? null : $this->resources->currentFor($type, (string) $record->getKey());
        // Only events carry their own timezone; everything else uses the organization's.
        $timezone = (string) (($record instanceof Event ? $record->timezone : null) ?? $settings->string('site.timezone'));

        $values = [
            'status' => $record === null ? 'draft' : (string) $record->getRawOriginal('status'),
            'slug' => (string) ($row?->getAttribute('slug') ?? ''),
        ];
        foreach ($definition['fields'] as $field) {
            $source = $definition['version'] !== null && ($field['on'] ?? 'version') === 'version' ? $row : $record;
            $value = $source?->getAttribute($field['name']) ?? ($field['name'] === 'timezone' ? $timezone : null);
            $values[$field['name']] = $this->formValue($field, $value, isset($field['timezoneField']) ? $timezone : (string) config('app.timezone'));
        }

        $hasMedia = collect($definition['fields'])->contains(fn (array $field): bool => $field['type'] === 'media');

        return WorkspacePage::render('admin.website.edit', [
            'collection' => WebsiteCollections::present($definition),
            'collections' => $this->navigation($request),
            'item' => $record === null ? null : [
                'id' => (string) $record->getKey(),
                'reference' => $record instanceof Vacancy ? $record->reference_no : null,
                'liveUrl' => $current === null ? null : $this->urls->path($type, (string) $current->getAttribute('slug')),
            ],
            'values' => $values,
            'media' => $hasMedia ? $this->imageOptions() : [],
            'history' => $record === null ? [] : AuditEvent::query()
                ->with('actor:id,name')
                ->where('auditable_type', $record::class)
                ->where('auditable_id', $record->getKey())
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(AuditEventPresenter::present(...))
                ->all(),
        ]);
    }

    /** @param  array<string, mixed>  $field */
    private function formValue(array $field, mixed $value, string $timezone): mixed
    {
        if ($value === null) {
            return $field['type'] === 'select' && ($field['required'] ?? false) ? $field['options'][0]['value'] : '';
        }

        return match ($field['type']) {
            'lines' => implode("\n", (array) $value),
            'datetime' => ($value instanceof CarbonInterface ? $value : CarbonImmutable::parse((string) $value, 'UTC'))
                ->setTimezone($timezone)
                ->format('Y-m-d\TH:i'),
            default => $value instanceof BackedEnum ? $value->value : $value,
        };
    }

    /** @param  array<string, mixed>  $definition */
    private function saved(array $definition, Model $record): RedirectResponse
    {
        $item = Str::ucfirst($definition['singular']);
        $live = in_array((string) $record->getRawOriginal('status'), WebsiteCollections::liveStatusValues($definition), true);

        return redirect()
            ->route('admin.website.edit', ['collection' => $definition['key'], 'record' => $record->getKey()])
            ->with('status', $live
                ? __(':item saved. The changes are live on the website.', ['item' => $item])
                : __(':item saved. It is not visible on the website.', ['item' => $item]));
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return Collection<string, int>
     */
    private function statusCounts(array $definition): Collection
    {
        return $definition['record']::query()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => (int) $count);
    }

    private function listMeta(Model $record): ?string
    {
        return match (true) {
            $record instanceof Event => $record->starts_at->setTimezone((string) ($record->timezone ?: 'UTC'))->format('d M Y, H:i'),
            $record instanceof Vacancy => $record->closes_at === null ? null : __('Closes :date', ['date' => $record->closes_at->format('d M Y')]),
            default => null,
        };
    }

    /** @return list<array{value: string, label: string}> */
    private function imageOptions(): array
    {
        return MediaAsset::query()
            ->where('visibility', MediaVisibility::Public)
            ->where('scan_status', MediaStatus::Clean)
            ->where('processing_status', MediaStatus::Ready)
            ->where('mime_type', 'like', 'image/%')
            ->orderBy('title')
            ->orderBy('original_name')
            ->get(['id', 'title', 'original_name'])
            ->map(fn (MediaAsset $asset): array => ['value' => (string) $asset->id, 'label' => (string) ($asset->title ?: $asset->original_name)])
            ->all();
    }

    /**
     * Tabs for every listing type the user may manage, with current counts.
     * Experts keep their own screens but sit alongside the other listings.
     *
     * @return list<array{key: string, label: string, href: string, count: int}>
     */
    private function navigation(Request $request): array
    {
        $user = $this->user($request);
        $tabs = collect(WebsiteCollections::permittedFor($user))
            ->map(fn (array $definition, string $key): array => [
                'key' => $key,
                'label' => $definition['label'],
                'href' => route('admin.website.collection', $key, false),
                'count' => $this->statusCounts($definition)->except(self::RETIRED_STATUSES)->sum(),
            ])
            ->values();
        if ($user->hasPermission('experts.manage')) {
            $tabs->splice(2, 0, [[
                'key' => 'experts',
                'label' => 'Experts',
                'href' => route('admin.experts.index', [], false),
                'count' => Expert::query()->whereNotIn('status', self::RETIRED_STATUSES)->count(),
            ]]);
        }

        return $tabs->all();
    }

    /** @return array<string, mixed> */
    private function definition(Request $request, string $collection): array
    {
        $definition = WebsiteCollections::find($collection) ?? abort(404);
        abort_unless($this->user($request)->hasPermission($definition['permission']), 403);

        return $definition;
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
