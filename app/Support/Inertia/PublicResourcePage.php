<?php

declare(strict_types=1);

namespace App\Support\Inertia;

use App\Enums\Seo\PublicResourceType;
use App\Models\Event;
use App\Models\Vacancy;
use App\Queries\Seo\PublicResourceQuery;
use App\Services\Seo\RedirectResolver;
use App\Services\Seo\SeoSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * Serves a resource detail URL so exactly one URL per resource answers 200:
 *
 * - current slug of a public resource: the page
 * - older slug of a public resource: 301 to the current slug
 * - managed redirect for the path: 301/302 to its destination
 * - archived, cancelled or closed resource: 410 Gone (configurable)
 * - anything else, including drafts: 404
 */
final class PublicResourcePage
{
    public static function show(PublicResourceType $type, string $slug): Response|RedirectResponse
    {
        $resolved = app(PublicResourceQuery::class)->resolve($type, $slug);
        $record = $resolved['record'];

        if ($resolved['state'] === 'current' && $record !== null) {
            return match ($type) {
                PublicResourceType::Event => PublicContent::event($record instanceof Event ? $record : abort(404)),
                PublicResourceType::Vacancy => PublicContent::vacancy($record instanceof Vacancy ? $record : abort(404)),
                default => PublicContent::detail($type, $record),
            };
        }

        if ($resolved['state'] === 'moved' && $record !== null) {
            return redirect()->route($type->showRoute(), ['slug' => $record->getAttribute('slug')], 301);
        }

        $managed = app(RedirectResolver::class)->resolve(route($type->showRoute(), ['slug' => $slug], false));
        if ($managed !== null) {
            abort_if($managed->destination === null, 410);

            return redirect()->to($managed->destination, $managed->status);
        }

        abort_if($resolved['state'] === 'gone' && app(SeoSettings::class)->archivedContentIsGone(), 410);
        abort(404);
    }
}
