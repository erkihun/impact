<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CaseStudyVersion;
use Illuminate\Contracts\View\View;

final class CaseStudyController extends Controller
{
    public function index(): View
    {
        return view('public.collection', [
            'eyebrow' => __('Evidence'),
            'title' => __('Case studies'),
            'items' => CaseStudyVersion::query()->where('locale', app()->getLocale())
                ->publiclyVisible()
                ->latest()->paginate(12),
            'routePrefix' => 'case-studies.show',
            'nameField' => 'title',
            'description' => __('Review authorized examples that connect context, intervention and outcomes without exposing confidential client information.'),
        ]);
    }

    public function show(string $locale, string $slug): View
    {
        return view('public.detail', [
            'item' => CaseStudyVersion::query()->where(compact('locale', 'slug'))
                ->publiclyVisible()->firstOrFail(),
            'titleField' => 'title',
        ]);
    }
}
