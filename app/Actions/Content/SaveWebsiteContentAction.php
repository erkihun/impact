<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Contracts\AuditRecorder;
use App\Contracts\PublicReferenceGenerator;
use App\Data\Audit\AuditData;
use App\Models\Event;
use App\Models\Industry;
use App\Models\Insight;
use App\Models\Service;
use App\Models\User;
use App\Models\Vacancy;
use App\Queries\Seo\PublicResourceQuery;
use App\Support\WebsiteCollections;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates or updates one website listing (service, industry, case study,
 * insight, event or vacancy) from an editor's form.
 *
 * The status lives on the record; versioned resources keep their text on
 * the latest English version, which is edited in place and published with
 * the record. Slug redirects, sitemap, search and CDN sync follow from the
 * public resource observers.
 */
final readonly class SaveWebsiteContentAction
{
    public function __construct(
        private AuditRecorder $audit,
        private PublicReferenceGenerator $references,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $validated
     */
    public function execute(User $actor, array $definition, ?Model $record, array $validated, string $correlationId): Model
    {
        return DB::transaction(function () use ($actor, $definition, $record, $validated, $correlationId): Model {
            $creating = $record === null;
            $record ??= new ($definition['record']);
            $beforeHash = $creating ? null : hash('sha256', $record->toJson());
            $previousStatus = $creating ? null : (string) $record->getRawOriginal('status');
            $versioned = $definition['version'] !== null;

            $recordValues = ['status' => $validated['status']];
            $rowValues = ['slug' => $validated['slug']];
            foreach ($definition['fields'] as $field) {
                $value = $this->value($field, $validated);
                if ($versioned && ($field['on'] ?? 'version') === 'version') {
                    $rowValues[$field['name']] = $value;
                } else {
                    $recordValues[$field['name']] = $value;
                }
            }
            if (! $versioned) {
                $recordValues = [...$recordValues, ...$rowValues];
            }
            if ($creating) {
                $recordValues = [...$this->creationDefaults($record, $actor, $validated['slug']), ...$recordValues];
            }
            if ($record instanceof Insight && $validated['status'] === 'published' && $record->getAttribute('published_at') === null) {
                $recordValues['published_at'] = CarbonImmutable::now('UTC');
            }

            $record->fill($recordValues)->save();

            if ($versioned) {
                $row = WebsiteCollections::editableRow($definition, $record) ?? new ($definition['version'])([
                    $definition['resource']->parentKey() => $record->getKey(),
                    'locale' => PublicResourceQuery::SITE_LOCALE,
                    'version_no' => 1,
                ]);
                $row->fill([...$rowValues, 'workflow_state' => $validated['status']])->save();
            }

            $this->audit->record(new AuditData(
                action: 'website_content.'.($creating ? 'created' : 'updated'),
                auditableType: $record::class,
                auditableId: (string) $record->getKey(),
                actorId: (string) $actor->getKey(),
                correlationId: $correlationId,
                beforeHash: $beforeHash,
                afterHash: hash('sha256', $record->fresh()->toJson()),
                metadata: [
                    'collection' => $definition['key'],
                    'title' => (string) ($validated[$definition['title']] ?? ''),
                    'from_status' => $previousStatus,
                    'to_status' => $validated['status'],
                ],
            ));

            return $record;
        }, attempts: 3);
    }

    /** @param  array<string, mixed>  $field */
    private function value(array $field, array $validated): mixed
    {
        $value = $validated[$field['name']] ?? null;

        return match ($field['type']) {
            'lines' => collect(preg_split('/\R/', (string) $value) ?: [])
                ->map(static fn (string $line): string => trim($line))
                ->filter()
                ->values()
                ->all(),
            'number' => filled($value) ? (int) $value : null,
            // Times are entered as wall-clock time in the item's timezone
            // (events) or the organization timezone, and stored in UTC.
            'datetime' => filled($value)
                ? CarbonImmutable::parse((string) $value, (string) ($validated[$field['timezoneField'] ?? ''] ?? config('app.timezone')))->utc()
                : null,
            default => filled($value) ? $value : null,
        };
    }

    /** @return array<string, mixed> */
    private function creationDefaults(Model $record, User $actor, string $slug): array
    {
        $code = Str::upper(Str::limit(str_replace('-', '', $slug), 20, '')).'-'.Str::upper(Str::random(6));

        return match (true) {
            $record instanceof Service => ['code' => $code, 'created_by' => $actor->getKey()],
            $record instanceof Industry => ['code' => $code],
            $record instanceof Insight => ['type' => 'article'],
            $record instanceof Event => ['locale' => PublicResourceQuery::SITE_LOCALE],
            $record instanceof Vacancy => [
                'locale' => PublicResourceQuery::SITE_LOCALE,
                'reference_no' => $this->references->generate('VAC'),
            ],
            default => [],
        };
    }
}
