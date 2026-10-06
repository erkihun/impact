<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\Seo\PublicResourceType;
use App\Models\CaseStudy;
use App\Models\Expert;
use App\Models\Industry;
use App\Models\Insight;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Queries\Seo\PublicResourceQuery;
use App\Services\Seo\PublicationSeoSync;
use App\Services\Seo\PublicUrlGenerator;
use Illuminate\Database\Eloquent\Model;

/**
 * Status changes on a resource (publish, unpublish, archive, consent
 * withdrawal) and SEO overrides change what is public without touching the
 * version rows, so they trigger the same sitemap/search/CDN sync.
 */
final readonly class PublicResourceParentObserver
{
    public function __construct(
        private PublicationSeoSync $sync,
        private PublicUrlGenerator $urls,
    ) {}

    public function saved(Model $model): void
    {
        if ($model instanceof SeoMetadata || $model->wasChanged() || $model->wasRecentlyCreated) {
            $this->sync->changed([$this->path($model)]);
        }
    }

    public function deleted(Model $model): void
    {
        $this->sync->changed([$this->path($model)]);
    }

    private function path(Model $model): ?string
    {
        $type = match (true) {
            $model instanceof Service => PublicResourceType::Service,
            $model instanceof Industry => PublicResourceType::Industry,
            $model instanceof Expert => PublicResourceType::Expert,
            $model instanceof CaseStudy => PublicResourceType::CaseStudy,
            $model instanceof Insight => PublicResourceType::Insight,
            default => null,
        };
        if ($type === null) {
            return null;
        }

        $slug = $type->modelClass()::query()
            ->where($type->parentKey(), $model->getKey())
            ->where('locale', PublicResourceQuery::SITE_LOCALE)
            ->orderByRaw("CASE WHEN workflow_state = 'published' THEN 0 ELSE 1 END")
            ->orderByDesc('version_no')
            ->value('slug');

        return is_string($slug) ? $this->urls->path($type, $slug) : null;
    }
}
