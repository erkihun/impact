<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Support\Inertia\PublicContent;
use Inertia\Response;

final class VacancyController extends Controller
{
    public function index(): Response
    {
        return PublicContent::collection([
            'eyebrow' => __('Build your career'),
            'title' => __('Careers and opportunities'),
            'items' => Vacancy::query()->where('locale', app()->getLocale())
                ->where('status', 'published')->orderBy('closes_at')->paginate(12),
            'routePrefix' => 'careers.show',
            'nameField' => 'title',
        ]);
    }

    public function show(string $locale, string $slug): Response
    {
        return PublicContent::vacancy(
            Vacancy::query()->where(compact('locale', 'slug'))
                ->where('status', 'published')->firstOrFail(),
        );
    }
}
