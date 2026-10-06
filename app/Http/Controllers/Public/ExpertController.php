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

final class ExpertController extends Controller
{
    public function index(PublicResourceQuery $resources): Response
    {
        return PublicContent::collection([
            'type' => PublicResourceType::Expert,
            'eyebrow' => __('Our people'),
            'title' => __('Experts'),
            'items' => $resources->listing(PublicResourceType::Expert)
                ->with('expert.profileMedia.variants')
                ->orderBy('display_name')
                ->paginate(12),
            'description' => __('Find approved specialist profiles by name, role or area of experience.'),
        ]);
    }

    public function show(string $slug): Response|RedirectResponse
    {
        return PublicResourcePage::show(PublicResourceType::Expert, $slug);
    }
}
