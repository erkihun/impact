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

final class CaseStudyController extends Controller
{
    public function index(PublicResourceQuery $resources): Response
    {
        return PublicContent::collection([
            'type' => PublicResourceType::CaseStudy,
            'eyebrow' => __('Evidence'),
            'title' => __('Case studies'),
            'items' => $resources->listing(PublicResourceType::CaseStudy)->latest()->orderBy('slug')->paginate(12),
            'description' => __('Review authorized examples that connect context, intervention and outcomes without exposing confidential client information.'),
        ]);
    }

    public function show(string $slug): Response|RedirectResponse
    {
        return PublicResourcePage::show(PublicResourceType::CaseStudy, $slug);
    }
}
