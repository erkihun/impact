<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Enums\Seo\PublicResourceType;
use App\Models\ContentRelation;
use App\Queries\Seo\PublicResourceQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Contextual internal links between public resources.
 *
 * Links come only from real relationships: the service/industry and
 * expert/service assignments plus editor-curated relations. Unpublished
 * targets are never linked and each group is capped so pages do not turn
 * into link lists.
 */
final readonly class InternalLinkService
{
    private const GROUP_LIMIT = 6;

    public function __construct(
        private PublicResourceQuery $resources,
        private PublicUrlGenerator $urls,
    ) {}

    /** @return list<PublicResourceType> */
    public function targetsFor(PublicResourceType $type): array
    {
        return match ($type) {
            PublicResourceType::Service => [PublicResourceType::Industry, PublicResourceType::Expert, PublicResourceType::CaseStudy, PublicResourceType::Insight],
            PublicResourceType::Industry => [PublicResourceType::Service, PublicResourceType::Expert, PublicResourceType::CaseStudy, PublicResourceType::Insight],
            PublicResourceType::Expert => [PublicResourceType::Service, PublicResourceType::CaseStudy, PublicResourceType::Insight],
            PublicResourceType::CaseStudy => [PublicResourceType::Service, PublicResourceType::Industry, PublicResourceType::Expert],
            PublicResourceType::Insight => [PublicResourceType::Service, PublicResourceType::Expert, PublicResourceType::Insight],
            PublicResourceType::Event => [PublicResourceType::Service, PublicResourceType::Insight, PublicResourceType::Expert],
            PublicResourceType::Vacancy => [],
        };
    }

    public function identity(PublicResourceType $type, Model $record): string
    {
        return (string) $record->getAttribute($type->parentKey());
    }

    /**
     * @return list<array{type: string, heading: string, items: list<array{name: string, href: string, summary: string|null}>}>
     */
    public function relatedFor(PublicResourceType $type, Model $record): array
    {
        $id = $this->identity($type, $record);
        $related = $this->relatedIds($type, $id);
        $groups = [];

        foreach ($this->targetsFor($type) as $target) {
            $items = $this->publicRecords($target, $related[$target->value] ?? [])
                ->reject(fn (Model $item): bool => $target === $type && $this->identity($target, $item) === $id)
                ->take(self::GROUP_LIMIT);

            if ($items->isEmpty() && $type === PublicResourceType::Insight && $target === PublicResourceType::Insight) {
                $items = $this->resources->current(PublicResourceType::Insight)
                    ->reject(fn (Model $item): bool => $this->identity($target, $item) === $id)
                    ->sortByDesc(fn (Model $item): string => (string) data_get($item, 'insight.published_at'))
                    ->take(3);
            }
            if ($items->isEmpty()) {
                continue;
            }

            $groups[] = [
                'type' => $target->value,
                'heading' => $this->heading($type, $target),
                'items' => $items->map(fn (Model $item): array => $this->link($target, $item))->values()->all(),
            ];
        }

        return $groups;
    }

    /**
     * Related resource ids by type, from pivots and curated relations in
     * both directions.
     *
     * @return array<string, list<string>>
     */
    public function relatedIds(PublicResourceType $type, string $id): array
    {
        $related = [];
        $push = static function (string $relatedType, string $relatedId) use (&$related): void {
            $related[$relatedType][] = $relatedId;
        };

        ContentRelation::query()
            ->where(fn (Builder $query) => $query->where('source_type', $type->value)->where('source_id', $id))
            ->orderBy('sort_order')
            ->get()
            ->each(fn (ContentRelation $relation) => $push($relation->target_type, $relation->target_id));
        ContentRelation::query()
            ->where(fn (Builder $query) => $query->where('target_type', $type->value)->where('target_id', $id))
            ->orderBy('sort_order')
            ->get()
            ->each(fn (ContentRelation $relation) => $push($relation->source_type, $relation->source_id));

        if ($type === PublicResourceType::Service) {
            DB::table('service_industry')->where('service_id', $id)->orderBy('sort_order')->pluck('industry_id')
                ->each(fn (string $industry) => $push(PublicResourceType::Industry->value, $industry));
            DB::table('expert_service')->where('service_id', $id)->orderBy('sort_order')->pluck('expert_id')
                ->each(fn (string $expert) => $push(PublicResourceType::Expert->value, $expert));
        }
        if ($type === PublicResourceType::Industry) {
            DB::table('service_industry')->where('industry_id', $id)->orderBy('sort_order')->pluck('service_id')
                ->each(fn (string $service) => $push(PublicResourceType::Service->value, $service));
        }
        if ($type === PublicResourceType::Expert) {
            DB::table('expert_service')->where('expert_id', $id)->orderBy('sort_order')->pluck('service_id')
                ->each(fn (string $service) => $push(PublicResourceType::Service->value, $service));
        }

        return array_map(static fn (array $ids): array => array_values(array_unique($ids)), $related);
    }

    /** Number of public resources contextually linked with this one. */
    public function contextualLinkCount(PublicResourceType $type, string $id): int
    {
        $count = 0;
        foreach ($this->relatedIds($type, $id) as $relatedType => $ids) {
            $target = PublicResourceType::tryFrom($relatedType);
            if ($target !== null) {
                $count += $this->publicRecords($target, $ids)->count();
            }
        }

        return $count;
    }

    /**
     * @param  list<string>  $ids
     * @return Collection<int, Model>
     */
    private function publicRecords(PublicResourceType $type, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $records = $this->resources->visible($type)
            ->whereIn($type->parentKey(), $ids)
            ->when($type->isVersioned(), fn (Builder $query): Builder => $query->orderByDesc('version_no'))
            ->get()
            ->unique(fn (Model $record): string => $this->identity($type, $record));
        $order = array_flip($ids);

        return $records->sortBy(fn (Model $record): int => $order[$this->identity($type, $record)] ?? PHP_INT_MAX)->values();
    }

    /** @return array{name: string, href: string, summary: string|null} */
    private function link(PublicResourceType $type, Model $record): array
    {
        // data_get: each resource family has a different summary column.
        $summary = data_get($record, 'summary')
            ?? data_get($record, 'excerpt')
            ?? data_get($record, 'professional_title')
            ?? data_get($record, 'challenge')
            ?? data_get($record, 'description');

        return [
            'name' => (string) $record->getAttribute($type->titleField()),
            'href' => $this->urls->path($type, (string) $record->getAttribute('slug')),
            'summary' => filled($summary) ? Str::limit(trim(strip_tags((string) $summary)), 140) : null,
        ];
    }

    private function heading(PublicResourceType $source, PublicResourceType $target): string
    {
        return match ($target) {
            PublicResourceType::Service => __('Related services'),
            PublicResourceType::Industry => $source === PublicResourceType::Service ? __('Industries we support with this service') : __('Relevant industries'),
            PublicResourceType::Expert => $source === PublicResourceType::CaseStudy ? __('Experts involved') : __('Experts in this area'),
            PublicResourceType::CaseStudy => __('Related case studies'),
            PublicResourceType::Insight => $source === PublicResourceType::Insight ? __('Related insights') : __('Insights on this topic'),
            default => __('Related content'),
        };
    }
}
