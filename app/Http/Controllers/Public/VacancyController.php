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

final class VacancyController extends Controller
{
    public function index(PublicResourceQuery $resources): Response
    {
        return PublicContent::collection([
            'type' => PublicResourceType::Vacancy,
            'eyebrow' => __('Build your career'),
            'title' => __('Careers and opportunities'),
            'items' => $resources->listing(PublicResourceType::Vacancy)->orderBy('closes_at')->paginate(12),
            'description' => __('Explore current consulting roles and opportunities to work with Impact Consulting on strategy, research and institutional development.'),
        ]);
    }

    public function show(string $slug): Response|RedirectResponse
    {
        return PublicResourcePage::show(PublicResourceType::Vacancy, $slug);
    }
}
