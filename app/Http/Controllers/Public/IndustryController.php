<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Seo\PublicResourceType;
use App\Http\Controllers\Controller;
use App\Queries\Seo\PublicResourceQuery;
use App\Support\Inertia\PublicContent;
use App\Support\Inertia\PublicResourcePage;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class IndustryController extends Controller
{
    public function index(PublicResourceQuery $resources): Response
    {
        return PublicContent::collection([
            'type' => PublicResourceType::Industry,
            'eyebrow' => __('Sector insight'),
            'title' => __('Industries'),
            'items' => $resources->listing(PublicResourceType::Industry)->orderBy('name')->paginate(12),
            'description' => __('Find sector-aware advice grounded in institutional, regulatory and operating context.'),
        ]);
    }

    public function show(string $slug): Response|RedirectResponse
    {
        return PublicResourcePage::show(PublicResourceType::Industry, $slug);
    }
}
