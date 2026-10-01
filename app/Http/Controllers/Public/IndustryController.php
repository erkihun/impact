<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\IndustryVersion;
use App\Support\Inertia\PublicContent;
use Inertia\Response;

final class IndustryController extends Controller
{
    public function index(): Response
    {
        return PublicContent::collection([
            'eyebrow' => __('Sector insight'),
            'title' => __('Industries'),
            'items' => IndustryVersion::query()->where('locale', app()->getLocale())
                ->publiclyVisible()
                ->orderBy('name')->paginate(12),
            'routePrefix' => 'industries.show',
            'nameField' => 'name',
            'description' => __('Find sector-aware advice grounded in institutional, regulatory and operating context.'),
        ]);
    }

    public function show(string $locale, string $slug): Response
    {
        return PublicContent::detail([
            'item' => IndustryVersion::query()->where(compact('locale', 'slug'))
                ->publiclyVisible()->firstOrFail(),
            'titleField' => 'name',
        ]);
    }
}
