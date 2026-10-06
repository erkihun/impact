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

final class ServiceController extends Controller
{
    public function index(PublicResourceQuery $resources): Response
    {
        return PublicContent::collection([
            'type' => PublicResourceType::Service,
            'eyebrow' => __('What we do'),
            'title' => __('Consulting services'),
            'items' => $resources->listing(PublicResourceType::Service)->orderBy('name')->paginate(12),
            'description' => __('Explore advisory services organized around client challenges, delivery needs and measurable outcomes.'),
        ]);
    }

    public function show(string $slug): Response|RedirectResponse
    {
        return PublicResourcePage::show(PublicResourceType::Service, $slug);
    }
}
