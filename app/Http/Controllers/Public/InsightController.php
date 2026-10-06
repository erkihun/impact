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

final class InsightController extends Controller
{
    public function index(PublicResourceQuery $resources): Response
    {
        return PublicContent::collection([
            'type' => PublicResourceType::Insight,
            'eyebrow' => __('Knowledge centre'),
            'title' => __('Insights'),
            'items' => $resources->listing(PublicResourceType::Insight)->latest()->orderBy('slug')->paginate(12),
            'description' => __('Browse current articles, reports and practical learning published by Impact Consulting.'),
        ]);
    }

    public function show(string $slug): Response|RedirectResponse
    {
        return PublicResourcePage::show(PublicResourceType::Insight, $slug);
    }
}
