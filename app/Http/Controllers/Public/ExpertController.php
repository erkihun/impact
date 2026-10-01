<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ExpertVersion;
use App\Support\Inertia\PublicContent;
use Inertia\Response;

final class ExpertController extends Controller
{
    public function index(): Response
    {
        return PublicContent::collection([
            'eyebrow' => __('Our people'),
            'title' => __('Experts'),
            'items' => ExpertVersion::query()->where('locale', app()->getLocale())
                ->with('expert.profileMedia')
                ->publiclyVisible()
                ->orderBy('display_name')->paginate(4),
            'routePrefix' => 'experts.show',
            'nameField' => 'display_name',
            'description' => __('Find approved specialist profiles by name, role or area of experience.'),
        ]);
    }

    public function show(string $locale, string $slug): Response
    {
        return PublicContent::detail([
            'item' => ExpertVersion::query()->where(compact('locale', 'slug'))
                ->with('expert.profileMedia')
                ->publiclyVisible()->firstOrFail(),
            'titleField' => 'display_name',
        ]);
    }
}
