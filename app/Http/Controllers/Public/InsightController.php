<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\InsightVersion;
use Illuminate\Contracts\View\View;

final class InsightController extends Controller
{
    public function index(): View
    {
        return view('public.collection', [
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

    public function show(string $locale, string $slug): View
    {
        return view('public.detail', [
            'item' => InsightVersion::query()->where(compact('locale', 'slug'))
                ->publiclyVisible()->firstOrFail(),
            'titleField' => 'title',
        ]);
    }
}
