<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\InsightVersion;
use App\Support\Inertia\PublicContent;
use Inertia\Response;

final class InsightController extends Controller
{
    public function index(): Response
    {
        return PublicContent::collection([
            'eyebrow' => __('Knowledge centre'),
            'title' => __('Insights'),
            'items' => InsightVersion::query()->where('locale', app()->getLocale())
                ->publiclyVisible()
                ->latest()->paginate(12),
            'routePrefix' => 'insights.show',
            'nameField' => 'title',
            'description' => __('Browse current articles, reports and practical learning published by Impact Consulting.'),
        ]);
    }

    public function show(string $locale, string $slug): Response
    {
        return PublicContent::detail([
            'item' => InsightVersion::query()->where(compact('locale', 'slug'))
                ->publiclyVisible()->firstOrFail(),
            'titleField' => 'title',
        ]);
    }
}
