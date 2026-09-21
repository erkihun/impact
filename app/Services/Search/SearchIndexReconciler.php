<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Contracts\SearchIndexer;
use App\Data\Search\SearchReconciliationResult;
use App\Enums\ContentWorkflowState;
use App\Models\CaseStudyVersion;
use App\Models\ContentItem;
use App\Models\Event;
use App\Models\ExpertVersion;
use App\Models\IndustryVersion;
use App\Models\InsightVersion;
use App\Models\SearchDocument;
use App\Models\ServiceVersion;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

final readonly class SearchIndexReconciler
{
    public function __construct(private SearchIndexer $indexer) {}

    public function reconcile(): SearchReconciliationResult
    {
        /** @var array<string, true> $expected */
        $expected = [];
        $upserted = 0;

        ContentItem::query()
            ->with('currentVersion')
            ->where('status', ContentWorkflowState::Published->value)
            ->whereNotNull('current_version_id')
            ->chunkById(200, function ($items) use (&$expected, &$upserted): void {
                foreach ($items as $content) {
                    $version = $content->currentVersion;
                    if ($version === null
                        || $version->getRawOriginal('workflow_state') !== ContentWorkflowState::Published->value) {
                        continue;
                    }

                    $type = 'content_'.$content->getRawOriginal('type');
                    $this->indexer->upsert($type, $content->id, $version->locale, [
                        'title' => $version->title,
                        'summary' => $version->summary,
                        'body' => $this->searchableText($version->body),
                        'url' => "/{$version->locale}/{$version->slug}",
                        'filters' => [$content->getRawOriginal('type')],
                        'published_at' => $content->getRawOriginal('published_at'),
                    ]);
                    $expected[$this->key($type, $content->id, $version->locale)] = true;
                    $upserted++;
                }
            });

        $this->reconcileVersionStream(
            ServiceVersion::query()->publiclyVisible()
                ->orderBy('service_id')->orderBy('locale')->orderByDesc('version_no')->cursor(),
            'service',
            'service_id',
            fn (ServiceVersion $version): array => [
                'title' => $version->name,
                'summary' => $version->summary,
                'body' => $this->searchableText([
                    $version->problem_statement,
                    $version->approach,
                    $version->deliverables,
                    $version->benefits,
                ]),
                'url' => "/{$version->locale}/services/{$version->slug}",
                'filters' => ['service'],
                'published_at' => $version->created_at,
            ],
            $expected,
            $upserted,
        );
        $this->reconcileVersionStream(
            IndustryVersion::query()->publiclyVisible()
                ->orderBy('industry_id')->orderBy('locale')->orderByDesc('version_no')->cursor(),
            'industry',
            'industry_id',
            fn (IndustryVersion $version): array => [
                'title' => $version->name,
                'summary' => $version->summary,
                'body' => $this->searchableText([$version->overview, $version->challenges]),
                'url' => "/{$version->locale}/industries/{$version->slug}",
                'filters' => ['industry'],
                'published_at' => $version->created_at,
            ],
            $expected,
            $upserted,
        );
        $this->reconcileVersionStream(
            ExpertVersion::query()->publiclyVisible()
                ->orderBy('expert_id')->orderBy('locale')->orderByDesc('version_no')->cursor(),
            'expert',
            'expert_id',
            fn (ExpertVersion $version): array => [
                'title' => $version->display_name,
                'summary' => $version->professional_title,
                'body' => $this->searchableText([
                    $version->biography,
                    $version->qualifications,
                    $version->languages,
                ]),
                'url' => "/{$version->locale}/experts/{$version->slug}",
                'filters' => ['expert'],
                'published_at' => $version->created_at,
            ],
            $expected,
            $upserted,
        );
        $this->reconcileVersionStream(
            CaseStudyVersion::query()->publiclyVisible()
                ->orderBy('case_study_id')->orderBy('locale')->orderByDesc('version_no')->cursor(),
            'case_study',
            'case_study_id',
            fn (CaseStudyVersion $version): array => [
                'title' => $version->title,
                'summary' => $version->challenge,
                'body' => $this->searchableText([
                    $version->challenge,
                    $version->approach,
                    $version->solution,
                    $version->deliverables,
                    $version->outcomes,
                    $version->value_created,
                    $version->metrics,
                ]),
                'url' => "/{$version->locale}/case-studies/{$version->slug}",
                'filters' => ['case_study'],
                'published_at' => $version->created_at,
            ],
            $expected,
            $upserted,
        );
        $this->reconcileVersionStream(
            InsightVersion::query()->publiclyVisible()
                ->orderBy('insight_id')->orderBy('locale')->orderByDesc('version_no')->cursor(),
            'insight',
            'insight_id',
            fn (InsightVersion $version): array => [
                'title' => $version->title,
                'summary' => $version->excerpt,
                'body' => $version->body ?? '',
                'url' => "/{$version->locale}/insights/{$version->slug}",
                'filters' => ['insight'],
                'published_at' => $version->created_at,
            ],
            $expected,
            $upserted,
        );

        foreach (Event::query()
            ->whereIn('status', ['published', 'registration_open', 'registration_closed', 'completed'])
            ->cursor() as $event) {
            $this->indexer->upsert('event', $event->id, $event->locale, [
                'title' => $event->title,
                'summary' => str($event->description)->limit(300)->toString(),
                'body' => $event->description,
                'url' => "/{$event->locale}/events/{$event->slug}",
                'filters' => ['event', $event->getRawOriginal('format')],
                'published_at' => $event->created_at,
            ]);
            $expected[$this->key('event', $event->id, $event->locale)] = true;
            $upserted++;
        }
        foreach (Vacancy::query()->where('status', 'published')->cursor() as $vacancy) {
            $this->indexer->upsert('vacancy', $vacancy->id, $vacancy->locale, [
                'title' => $vacancy->title,
                'summary' => str($vacancy->description)->limit(300)->toString(),
                'body' => $this->searchableText([$vacancy->description, $vacancy->requirements]),
                'url' => "/{$vacancy->locale}/careers/{$vacancy->slug}",
                'filters' => ['vacancy', $vacancy->type],
                'published_at' => $vacancy->created_at,
            ]);
            $expected[$this->key('vacancy', $vacancy->id, $vacancy->locale)] = true;
            $upserted++;
        }

        $deleted = 0;
        SearchDocument::query()
            ->where(function ($query): void {
                $query->where('searchable_type', 'like', 'content_%')
                    ->orWhereIn('searchable_type', [
                        'service',
                        'industry',
                        'expert',
                        'case_study',
                        'insight',
                        'event',
                        'vacancy',
                    ]);
            })
            ->chunkById(200, function ($documents) use ($expected, &$deleted): void {
                foreach ($documents as $document) {
                    if (isset($expected[$this->key(
                        $document->searchable_type,
                        $document->searchable_id,
                        $document->locale,
                    )])) {
                        continue;
                    }

                    $document->delete();
                    $deleted++;
                }
            });

        return new SearchReconciliationResult($upserted, $deleted);
    }

    /**
     * @template TVersion of Model
     *
     * @param  LazyCollection<int, TVersion>  $versions
     * @param  callable(TVersion): array<string, mixed>  $document
     * @param  array<string, true>  $expected
     */
    private function reconcileVersionStream(
        LazyCollection $versions,
        string $type,
        string $parentKey,
        callable $document,
        array &$expected,
        int &$upserted,
    ): void {
        $previous = null;
        foreach ($versions as $version) {
            $group = $version->getAttribute($parentKey).'|'.$version->getAttribute('locale');
            if ($group === $previous) {
                continue;
            }
            $previous = $group;
            $id = (string) $version->getKey();
            $locale = (string) $version->getAttribute('locale');
            $this->indexer->upsert($type, $id, $locale, $document($version));
            $expected[$this->key($type, $id, $locale)] = true;
            $upserted++;
        }
    }

    /** @param array<array-key, mixed> $values */
    private function searchableText(array $values): string
    {
        return collect($values)
            ->flatten()
            ->filter(static fn (mixed $value): bool => is_scalar($value))
            ->map(static fn (mixed $value): string => (string) $value)
            ->join(' ');
    }

    private function key(string $type, string $id, string $locale): string
    {
        return "{$type}|{$id}|{$locale}";
    }
}
