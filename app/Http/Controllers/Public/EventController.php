<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\Inertia\PublicContent;
use Inertia\Response;

final class EventController extends Controller
{
    public function index(): Response
    {
        return PublicContent::collection([
            'eyebrow' => __('Connect and learn'),
            'title' => __('Events and webinars'),
            'items' => Event::query()->where('locale', app()->getLocale())
                ->whereIn('status', ['published', 'registration_open', 'registration_closed'])
                ->orderBy('starts_at')->paginate(12),
            'routePrefix' => 'events.show',
            'nameField' => 'title',
        ]);
    }

    public function show(string $locale, string $slug): Response
    {
        return PublicContent::event(
            Event::query()->where(compact('locale', 'slug'))
                ->whereNotIn('status', ['draft', 'cancelled'])->firstOrFail(),
        );
    }
}
