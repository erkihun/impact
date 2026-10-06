<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Enums\Seo\PublicResourceType;
use App\Enums\Seo\RobotsDirective;
use App\Http\Controllers\Controller;
use App\Models\ContentRelation;
use App\Models\MediaAsset;
use App\Models\Redirect;
use App\Models\SeoMetadata;
use App\Queries\Seo\PublicResourceQuery;
use App\Services\Seo\CanonicalUrlBuilder;
use App\Services\Seo\ContentSeoValidator;
use App\Services\Seo\InternalLinkService;
use App\Services\Seo\PageInspector;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Seo\SeoSettings;
use App\Services\Seo\SitemapBuilder;
use App\Support\CorrelationContext;
use App\Support\Inertia\WorkspacePage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/**
 * Per-page SEO editor: title, description, slug, indexing, canonical,
 * social card, sitemap inclusion, editorial intent fields and curated
 * related content, with search-result and social previews and live
 * validation.
 */
final class SeoPageController extends Controller
{
    public const SEARCH_INTENTS = ['informational', 'commercial', 'navigational', 'transactional'];

    public const GEOGRAPHIES = ['Ethiopia', 'Addis Ababa', 'East Africa', 'Africa', 'International'];

    public function __construct(
        private readonly PublicResourceQuery $resources,
        private readonly PublicUrlGenerator $urls,
        private readonly CanonicalUrlBuilder $canonical,
    ) {}

    public function edit(
        string $type,
        string $key,
        PageInspector $inspector,
        ContentSeoValidator $validator,
        InternalLinkService $links,
        SeoSettings $settings,
    ): Response {
        $subject = $this->subject($type, $key);
        $override = SeoMetadata::query()->where('subject_type', $type)->where('subject_key', $key)->first();
        $page = $subject['public'] ? $inspector->inspect($subject['path']) : null;
        $seo = $page['seo'] ?? [];
        $resourceType = $subject['resourceType'];

        $validation = $validator->validate([
            'title' => $seo['title'] ?? $subject['name'],
            'seo_title' => $override?->meta_title,
            'description' => $override->meta_description ?? $seo['description'] ?? null,
            'h1' => $page === null ? $subject['name'] : ($page['h1'][0] ?? null),
            'canonical' => $override?->canonical_path,
            'robots' => $override?->robots,
            'social_image' => $override?->social_image_media_id !== null || ! empty($seo['image']['url']),
            'contextual_links' => $resourceType === null ? null : $links->contextualLinkCount($resourceType, $key),
            'structured_data' => $seo['structuredDataTypes'] ?? [],
            ...($subject['slug'] !== null ? ['slug' => $subject['slug']] : []),
        ]);

        return WorkspacePage::render('admin.seo.edit', [
            'subject' => [
                ...collect($subject)->except(['record', 'resourceType'])->all(),
                'canonicalUrl' => $this->canonical->url($subject['path']),
            ],
            'values' => [
                'meta_title' => $override->meta_title ?? '',
                'meta_description' => $override->meta_description ?? '',
                'slug' => $subject['slug'] ?? '',
                'robots' => $override->robots ?? '',
                'canonical_path' => $override->canonical_path ?? '',
                'include_in_sitemap' => $override->include_in_sitemap ?? true,
                'social_title' => $override->social_title ?? '',
                'social_description' => $override->social_description ?? '',
                'social_image_media_id' => $override->social_image_media_id ?? '',
                'primary_topic' => $override->primary_topic ?? '',
                'secondary_topics' => implode(', ', (array) ($override->secondary_topics ?? [])),
                'target_audience' => $override->target_audience ?? '',
                'search_intent' => $override->search_intent ?? '',
                'geographic_relevance' => (array) ($override->geographic_relevance ?? []),
                'related' => $resourceType === null ? [] : ContentRelation::query()
                    ->where('source_type', $type)->where('source_id', $key)->orderBy('sort_order')
                    ->get()->map(static fn (ContentRelation $relation): string => $relation->target_type.':'.$relation->target_id)->all(),
            ],
            'preview' => [
                'title' => $seo['title'] ?? null,
                'description' => $seo['description'] ?? null,
                'canonical' => $seo['canonical'] ?? null,
                'robots' => $seo['intendedRobots'] ?? null,
                'effectiveRobots' => $seo['robots'] ?? null,
                'image' => $seo['image'] ?? null,
                'socialTitle' => $seo['socialTitle'] ?? null,
                'socialDescription' => $seo['socialDescription'] ?? null,
                'structuredData' => $seo['structuredDataTypes'] ?? [],
                'h1' => $page['h1'] ?? [],
                'siteName' => $settings->siteName(),
                'host' => $settings->canonicalHost(),
            ],
            'validation' => $validation->toArray(),
            'relatedOptions' => $resourceType === null ? [] : $this->relatedOptions($resourceType, $key),
            'redirects' => Redirect::query()
                ->where(fn ($query) => $query->where(fn ($subjectQuery) => $subjectQuery->where('subject_type', $type)->where('subject_key', $key))
                    ->orWhere('destination_url', $subject['path']))
                ->orderByDesc('updated_at')
                ->get(['id', 'source_path', 'destination_url', 'status_code', 'origin', 'hit_count', 'enabled']),
            'mediaOptions' => MediaAsset::query()
                ->where('visibility', 'public')->where('scan_status', 'clean')->where('processing_status', 'ready')
                ->where('mime_type', 'like', 'image/%')->latest()->limit(200)
                ->get(['id', 'title', 'original_name'])
                ->map(static fn (MediaAsset $asset): array => ['value' => (string) $asset->id, 'label' => (string) ($asset->title ?: $asset->original_name)])
                ->prepend(['value' => '', 'label' => 'Use the page or default image'])
                ->values(),
            'intents' => array_merge(['' => 'Not set'], array_combine(self::SEARCH_INTENTS, array_map('ucfirst', self::SEARCH_INTENTS))),
            'geographies' => self::GEOGRAPHIES,
            'robotsOptions' => [
                '' => 'Default (index, follow when published)',
                RobotsDirective::NoindexFollow->value => 'noindex, follow',
                RobotsDirective::NoindexNofollow->value => 'noindex, nofollow',
            ],
        ]);
    }

    public function update(
        Request $request,
        string $type,
        string $key,
        AuditRecorder $audit,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $subject = $this->subject($type, $key);
        $resourceType = $subject['resourceType'];
        $validated = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'min:50', 'max:170'],
            'slug' => $subject['slug'] === null ? ['prohibited'] : [
                'required', 'string', 'max:80', 'regex:'.ContentSeoValidator::SLUG_PATTERN, Rule::notIn(ContentSeoValidator::RESERVED_SLUGS),
            ],
            'robots' => ['nullable', Rule::in([RobotsDirective::NoindexFollow->value, RobotsDirective::NoindexNofollow->value])],
            'canonical_path' => ['nullable', 'string', 'max:255'],
            'include_in_sitemap' => ['required', 'boolean'],
            'social_title' => ['nullable', 'string', 'max:95'],
            'social_description' => ['nullable', 'string', 'max:200'],
            'social_image_media_id' => ['nullable', 'uuid', Rule::exists('media_assets', 'id')->where('visibility', 'public')->where('scan_status', 'clean')->where('processing_status', 'ready')],
            'primary_topic' => ['nullable', 'string', 'max:120'],
            'secondary_topics' => ['nullable', 'string', 'max:500'],
            'target_audience' => ['nullable', 'string', 'max:160'],
            'search_intent' => ['nullable', Rule::in(self::SEARCH_INTENTS)],
            'geographic_relevance' => ['nullable', 'array'],
            'geographic_relevance.*' => [Rule::in(self::GEOGRAPHIES)],
            'related' => ['nullable', 'array', 'max:24'],
            'related.*' => ['string', 'max:80'],
        ]);

        if (filled($validated['canonical_path'] ?? null) && $this->canonical->safeOverride((string) $validated['canonical_path']) === null) {
            throw ValidationException::withMessages(['canonical_path' => __('Use a path or URL on this website.')]);
        }
        $related = $resourceType === null ? [] : $this->validRelated($resourceType, $key, (array) ($validated['related'] ?? []));

        DB::transaction(function () use ($validated, $type, $key, $subject, $resourceType, $related, $request): void {
            $override = SeoMetadata::query()->firstOrNew(['subject_type' => $type, 'subject_key' => $key]);
            $override->forceFill([
                'meta_title' => $this->nullable($validated['meta_title'] ?? null),
                'meta_description' => $this->nullable($validated['meta_description'] ?? null),
                'robots' => $this->nullable($validated['robots'] ?? null),
                'canonical_path' => $this->nullable($validated['canonical_path'] ?? null),
                'include_in_sitemap' => (bool) $validated['include_in_sitemap'],
                'social_title' => $this->nullable($validated['social_title'] ?? null),
                'social_description' => $this->nullable($validated['social_description'] ?? null),
                'social_image_media_id' => $this->nullable($validated['social_image_media_id'] ?? null),
                'primary_topic' => $this->nullable($validated['primary_topic'] ?? null),
                'secondary_topics' => collect(explode(',', (string) ($validated['secondary_topics'] ?? '')))->map(static fn (string $topic): string => trim($topic))->filter()->take(10)->values()->all() ?: null,
                'target_audience' => $this->nullable($validated['target_audience'] ?? null),
                'search_intent' => $this->nullable($validated['search_intent'] ?? null),
                'geographic_relevance' => array_values((array) ($validated['geographic_relevance'] ?? [])) ?: null,
                'updated_by' => $request->user()?->getKey(),
            ])->save();

            if ($resourceType !== null) {
                ContentRelation::query()->where('source_type', $type)->where('source_id', $key)->delete();
                foreach ($related as $index => [$targetType, $targetId]) {
                    ContentRelation::query()->create([
                        'source_type' => $type, 'source_id' => $key,
                        'target_type' => $targetType, 'target_id' => $targetId,
                        'sort_order' => $index, 'created_by' => $request->user()?->getKey(),
                    ]);
                }
            }

            // Changing a published slug: the observer records the 301 from
            // the previous URL and refreshes sitemap, search and CDN.
            $record = $subject['record'];
            if ($record instanceof Model && $resourceType !== null && $subject['slug'] !== $validated['slug']) {
                $taken = $resourceType->modelClass()::query()
                    ->where('locale', PublicResourceQuery::SITE_LOCALE)
                    ->where('slug', $validated['slug'])
                    ->where($resourceType->parentKey(), '!=', $key)
                    ->exists();
                if ($taken) {
                    throw ValidationException::withMessages(['slug' => __('This slug is already used.')]);
                }
                $record->forceFill(['slug' => $validated['slug']])->save();
            }
        });

        $audit->record(new AuditData(
            action: 'seo.metadata.updated',
            auditableType: SeoMetadata::class,
            auditableId: null,
            actorId: (string) $request->user()?->getKey(),
            correlationId: $correlation->id(),
            metadata: ['subject_type' => $type, 'subject_key' => $key, 'slug_changed' => $subject['slug'] !== null && $subject['slug'] !== ($validated['slug'] ?? null)],
        ));

        return redirect()->route('admin.seo.pages.edit', ['type' => $type, 'key' => $key])->with('status', __('SEO settings saved.'));
    }

    /**
     * @return array{type: string, key: string, name: string, typeLabel: string, path: string, slug: string|null, public: bool, record: Model|null, resourceType: PublicResourceType|null}
     */
    private function subject(string $type, string $key): array
    {
        if ($type === 'page') {
            $route = array_search($key, SitemapBuilder::pageKeys(), true);
            abort_if($route === false, 404);

            return [
                'type' => 'page', 'key' => $key, 'typeLabel' => 'Page',
                'name' => str($key)->replace(['.index', 'legal.', '.'], ['', '', ' '])->headline()->toString(),
                'path' => route((string) $route, [], false), 'slug' => null, 'public' => true,
                'record' => null, 'resourceType' => null,
            ];
        }

        $resourceType = PublicResourceType::tryFrom($type) ?? abort(404);
        $record = $this->resources->currentFor($resourceType, $key);
        abort_if($record === null, 404);

        return [
            'type' => $type, 'key' => $key, 'typeLabel' => $resourceType->label(),
            'name' => (string) $record->getAttribute($resourceType->titleField()),
            'path' => $this->urls->path($resourceType, (string) $record->getAttribute('slug')),
            'slug' => (string) $record->getAttribute('slug'), 'public' => true,
            'record' => $record, 'resourceType' => $resourceType,
        ];
    }

    /** @return list<array{value: string, label: string, group: string}> */
    private function relatedOptions(PublicResourceType $source, string $key): array
    {
        $options = [];
        foreach (PublicResourceType::cases() as $type) {
            if ($type === PublicResourceType::Vacancy) {
                continue;
            }
            foreach ($this->resources->current($type) as $record) {
                $identity = (string) $record->getAttribute($type->parentKey());
                if ($type === $source && $identity === $key) {
                    continue;
                }
                $options[] = ['value' => $type->value.':'.$identity, 'label' => (string) $record->getAttribute($type->titleField()), 'group' => $type->pluralLabel()];
            }
        }

        return $options;
    }

    /**
     * @param  list<string>  $values
     * @return list<array{0: string, 1: string}>
     */
    private function validRelated(PublicResourceType $source, string $key, array $values): array
    {
        $allowed = collect($this->relatedOptions($source, $key))->pluck('value')->flip();
        $invalid = collect($values)->reject(static fn (string $value): bool => $allowed->has($value));
        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages(['related' => __('Related content must be published pages on this website.')]);
        }

        return collect($values)->unique()->map(static fn (string $value): array => explode(':', $value, 2))->values()->all();
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
