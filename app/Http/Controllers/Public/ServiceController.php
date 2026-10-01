<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ServiceVersion;
use App\Support\Inertia\PublicContent;
use Inertia\Response;

final class ServiceController extends Controller
{
    public function index(): Response
    {
        return PublicContent::collection([
            'eyebrow' => __('What we do'),
            'title' => __('Consulting services'),
            'items' => ServiceVersion::query()
                ->where('locale', app()->getLocale())
                ->publiclyVisible()
                ->orderBy('name')
                ->paginate(12),
            'routePrefix' => 'services.show',
            'nameField' => 'name',
            'description' => __('Explore advisory services organized around client challenges, delivery needs and measurable outcomes.'),
        ]);
    }

    public function show(string $locale, string $slug): Response
    {
        $version = ServiceVersion::query()
            ->where(compact('locale', 'slug'))
            ->publiclyVisible()
            ->firstOrFail();

        return PublicContent::detail(['item' => $version, 'titleField' => 'name']);
    }
}
