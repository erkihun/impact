<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use Illuminate\Contracts\View\View;

final class VacancyController extends Controller
{
    public function index(): View
    {
        return view('public.collection', [
            'eyebrow' => __('Build your career'),
            'title' => __('Careers and opportunities'),
            'items' => Vacancy::query()->where('locale', app()->getLocale())
                ->where('status', 'published')->orderBy('closes_at')->paginate(12),
            'routePrefix' => 'careers.show',
            'nameField' => 'title',
        ]);
    }

    public function show(string $locale, string $slug): View
    {
        return view('public.vacancy', [
            'vacancy' => Vacancy::query()->where(compact('locale', 'slug'))
                ->where('status', 'published')->firstOrFail(),
        ]);
    }
}
