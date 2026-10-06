<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Seo\SaveRedirectAction;
use App\Enums\Seo\PublicResourceType;
use App\Enums\Seo\RedirectOrigin;
use App\Models\Redirect;
use App\Queries\Seo\PublicResourceQuery;
use App\Services\Seo\PublicationSeoSync;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Seo\SeoSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use WeakMap;

/**
 * Keeps URLs governed when public content changes.
 *
 * - A changed published slug gets a permanent redirect from every
 *   previously published path to the current one (no chains).
 * - Deleting the last public version of a resource records 410 Gone.
 * - Every change to what is public triggers sitemap, search and CDN sync.
 *
 * Draft edits never touch the live URL: the current public version, and so
 * its slug, only changes when a version is published.
 */
final class PublicResourceObserver
{
    /** @var WeakMap|null previously public path (string|null), keyed by model instance */
    private static ?WeakMap $previous = null;

    public function __construct(
        private readonly PublicResourceQuery $resources,
        private readonly PublicUrlGenerator $urls,
        private readonly PublicationSeoSync $sync,
        private readonly SeoSettings $settings,
    ) {}

    public function updating(Model $model): void
    {
        $this->remember($model);
    }

    public function deleting(Model $model): void
    {
        $this->remember($model);
    }

    public function saved(Model $model): void
    {
        $type = PublicResourceType::fromModel($model);
        if ($type === null || $model->getAttribute('locale') !== PublicResourceQuery::SITE_LOCALE) {
            return;
        }

        $current = $this->resources->currentFor($type, (string) $model->getAttribute($type->parentKey()));
        $previous = self::store()[$model] ?? null;
        $currentPath = $current === null ? null : $this->urls->path($type, (string) $current->getAttribute('slug'));

        if ($current !== null && $this->settings->redirectSlugChanges()) {
            foreach ($this->previouslyPublishedPaths($type, $model, $previous) as $oldPath) {
                if ($oldPath !== $currentPath) {
                    $this->redirect($type, $current, $oldPath, (string) $currentPath, RedirectOrigin::SlugChange, 301);
                }
            }
        }

        $this->sync->changed([$previous, $currentPath]);
    }

    public function deleted(Model $model): void
    {
        $type = PublicResourceType::fromModel($model);
        $previous = self::store()[$model] ?? null;
        if ($type === null || $previous === null) {
            return;
        }

        $current = $this->resources->currentFor($type, (string) $model->getAttribute($type->parentKey()));
        if ($current === null) {
            $this->redirect($type, $model, $previous, null, RedirectOrigin::Retired, 410);
        } elseif ($this->settings->redirectSlugChanges()) {
            $this->redirect($type, $current, $previous, $this->urls->path($type, (string) $current->getAttribute('slug')), RedirectOrigin::SlugChange, 301);
        }

        $this->sync->changed([$previous]);
    }

    /** Captures the public path the record had before this write. */
    private function remember(Model $model): void
    {
        $type = PublicResourceType::fromModel($model);
        if ($type === null) {
            return;
        }

        $current = $this->resources->currentFor($type, (string) ($model->getOriginal($type->parentKey()) ?? $model->getAttribute($type->parentKey())));
        self::store()[$model] = $current !== null && (string) $current->getKey() === (string) $model->getKey()
            ? $this->urls->path($type, (string) $model->getOriginal('slug'))
            : null;
    }

    /** @return list<string> */
    private function previouslyPublishedPaths(PublicResourceType $type, Model $model, ?string $previous): array
    {
        $paths = $previous === null ? [] : [$previous];
        if ($type->isVersioned()) {
            $type->modelClass()::query()
                ->where('locale', PublicResourceQuery::SITE_LOCALE)
                ->where($type->parentKey(), $model->getAttribute($type->parentKey()))
                ->whereIn('workflow_state', ['published', 'unpublished', 'archived'])
                ->pluck('slug')
                ->each(function (string $slug) use (&$paths, $type): void {
                    $paths[] = $this->urls->path($type, $slug);
                });
        }

        return array_values(array_unique($paths));
    }

    private function redirect(PublicResourceType $type, Model $subject, string $from, ?string $to, RedirectOrigin $origin, int $status): void
    {
        $existing = Redirect::query()->where('source_path', $from)->first();
        if ($existing !== null && $existing->destination_url === $to && $existing->status_code === $status) {
            return;
        }

        try {
            app(SaveRedirectAction::class)->execute(
                source: $from,
                destination: $to,
                status: $status,
                origin: $origin,
                reason: $origin === RedirectOrigin::Retired
                    ? 'Published '.strtolower($type->label()).' deleted.'
                    : 'Published '.strtolower($type->label()).' URL changed.',
                actorId: auth()->id() === null ? null : (string) auth()->id(),
                existing: $existing,
                subjectType: $type->value,
                subjectKey: (string) $subject->getAttribute($type->parentKey()),
            );
        } catch (ValidationException $exception) {
            // Never block an editorial save on redirect bookkeeping; the audit
            // and redirect validation commands surface what was skipped.
            Log::warning('seo.slug_redirect_skipped', ['from' => $from, 'to' => $to, 'errors' => $exception->errors()]);
        }
    }

    private static function store(): WeakMap
    {
        return self::$previous ??= new WeakMap;
    }
}
