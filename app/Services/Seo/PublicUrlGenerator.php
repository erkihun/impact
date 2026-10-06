<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Enums\Seo\PublicResourceType;
use App\Models\ContentItem;
use App\Models\ContentVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

/**
 * Public paths and canonical URLs for resources. Search documents, sitemap
 * entries, internal links and structured data all use this, so a resource
 * has one URL everywhere.
 */
final readonly class PublicUrlGenerator
{
    public function __construct(private CanonicalUrlBuilder $canonical) {}

    public function path(PublicResourceType $type, string $slug): string
    {
        return route($type->showRoute(), ['slug' => $slug], false);
    }

    public function pathFor(Model $record): ?string
    {
        $type = PublicResourceType::fromModel($record);

        return $type === null ? null : $this->path($type, (string) $record->getAttribute('slug'));
    }

    public function url(PublicResourceType $type, string $slug): string
    {
        return $this->canonical->url($this->path($type, $slug));
    }

    public function urlFor(Model $record): ?string
    {
        $path = $this->pathFor($record);

        return $path === null ? null : $this->canonical->url($path);
    }

    /**
     * Public path of a generic CMS content item, when its type has a public
     * route (impact.seo.content_item_routes: type => route name taking a
     * slug). None is configured today: the website renders from the
     * dedicated resource tables, so search and sitemaps never invent a URL.
     */
    public function contentItemPath(ContentItem $item, ContentVersion $version): ?string
    {
        $route = config('impact.seo.content_item_routes.'.$item->getRawOriginal('type'));

        return is_string($route) && Route::has($route) ? route($route, ['slug' => $version->slug], false) : null;
    }

    public function indexUrl(PublicResourceType $type): string
    {
        return $this->canonical->forRoute($type->indexRoute());
    }
}
