<?php

declare(strict_types=1);

namespace App\Queries\Seo;

use App\Enums\Seo\PublicResourceType;
use App\Models\Insight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Answers "what is public right now" for every resource family.
 *
 * A versioned resource has exactly one current public version: the highest
 * published version of a published parent. Older published versions are
 * never served directly; their URLs redirect to the current one.
 */
final class PublicResourceQuery
{
    public const SITE_LOCALE = 'en';

    /** @var list<string> */
    public const PUBLIC_EVENT_STATES = ['published', 'registration_open', 'registration_closed', 'completed'];

    /** @var list<string> */
    public const LISTED_EVENT_STATES = ['published', 'registration_open', 'registration_closed'];

    /** @var list<string> version states that once had a public URL */
    public const PREVIOUSLY_PUBLIC_STATES = ['published', 'unpublished', 'archived'];

    /** @return Builder<Model> */
    public function visible(PublicResourceType $type): Builder
    {
        /** @var Builder<Model> $query */
        $query = $type->modelClass()::query()->where('locale', self::SITE_LOCALE);

        return match ($type) {
            PublicResourceType::Event => $query->whereIn('status', self::PUBLIC_EVENT_STATES),
            PublicResourceType::Vacancy => $query->where('status', 'published'),
            default => $query->scopes(['publiclyVisible']),
        };
    }

    /**
     * Visible records excluding superseded versions, for paginated listings.
     *
     * @return Builder<Model>
     */
    public function listing(PublicResourceType $type): Builder
    {
        $query = $this->visible($type);
        if (! $type->isVersioned()) {
            return $query;
        }

        $table = $query->getModel()->getTable();
        $parentKey = $type->parentKey();

        return $query->whereNotExists(static fn ($newer) => $newer
            ->selectRaw('1')
            ->from("{$table} as newer")
            ->whereColumn("newer.{$parentKey}", "{$table}.{$parentKey}")
            ->where('newer.locale', self::SITE_LOCALE)
            ->where('newer.workflow_state', 'published')
            ->whereColumn('newer.version_no', '>', "{$table}.version_no"));
    }

    /**
     * One current public record per resource, ordered for stable output.
     *
     * @return Collection<int, Model>
     */
    public function current(PublicResourceType $type): Collection
    {
        $records = $this->visible($type)
            ->with($this->relations($type))
            ->orderByDesc($type->isVersioned() ? 'version_no' : 'updated_at')
            ->get();

        return $records
            ->unique(fn (Model $record): string => (string) $record->getAttribute($type->parentKey()))
            ->sortBy(fn (Model $record): string => (string) $record->getAttribute('slug'))
            ->values();
    }

    public function currentFor(PublicResourceType $type, string $parentId): ?Model
    {
        return $this->visible($type)
            ->with($this->relations($type))
            ->where($type->parentKey(), $parentId)
            ->when($type->isVersioned(), fn (Builder $query): Builder => $query->orderByDesc('version_no'))
            ->first();
    }

    /**
     * Resolves a requested slug.
     *
     * @return array{state: 'current'|'moved'|'gone'|'missing', record: Model|null}
     */
    public function resolve(PublicResourceType $type, string $slug): array
    {
        /** @var Model|null $any */
        // Only slugs that were ever public count: a draft revision's proposed
        // slug must not resolve (or redirect) until it is published.
        $any = $type->modelClass()::query()
            ->where('locale', self::SITE_LOCALE)
            ->where('slug', $slug)
            ->when($type->isVersioned(), fn (Builder $query): Builder => $query
                ->whereIn('workflow_state', self::PREVIOUSLY_PUBLIC_STATES)
                ->orderByDesc('version_no'))
            ->first();

        if ($any === null) {
            return ['state' => 'missing', 'record' => null];
        }
        $parent = $this->parentRelation($type);
        if ($parent !== null) {
            $any->loadMissing($parent);
        }

        $current = $this->currentFor($type, (string) $any->getAttribute($type->parentKey()));
        if ($current !== null) {
            return [
                'state' => $current->getAttribute('slug') === $slug ? 'current' : 'moved',
                'record' => $current,
            ];
        }

        return ['state' => $this->isRetired($type, $any) ? 'gone' : 'missing', 'record' => null];
    }

    /** Archived (or cancelled / closed) resources are deliberately retired. */
    public function isRetired(PublicResourceType $type, Model $record): bool
    {
        $relation = $this->parentRelation($type);
        $parent = $relation === null ? $record : data_get($record, $relation);
        $status = $parent instanceof Model ? $parent->getRawOriginal('status') : null;

        return in_array($status, ['archived', 'cancelled', 'closed'], true)
            || ($parent instanceof Insight && $parent->expires_at?->isPast() === true);
    }

    public function parentRelation(PublicResourceType $type): ?string
    {
        return match ($type) {
            PublicResourceType::Service => 'service',
            PublicResourceType::Industry => 'industry',
            PublicResourceType::Expert => 'expert',
            PublicResourceType::CaseStudy => 'caseStudy',
            PublicResourceType::Insight => 'insight',
            default => null,
        };
    }

    /** @return list<string> */
    private function relations(PublicResourceType $type): array
    {
        return match ($type) {
            PublicResourceType::Service => ['service'],
            PublicResourceType::Industry => ['industry'],
            PublicResourceType::Expert => ['expert.profileMedia.variants'],
            PublicResourceType::CaseStudy => ['caseStudy'],
            PublicResourceType::Insight => ['insight.primaryMedia.variants'],
            default => [],
        };
    }
}
