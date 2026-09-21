<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Contracts\View\View;

final class EventController extends Controller
{
    public function index(): View
    {
        return view('public.collection', [
            'eyebrow' => __('Connect and learn'),
            'title' => __('Events and webinars'),
            'items' => Event::query()->where('locale', app()->getLocale())
                ->whereIn('status', ['published', 'registration_open', 'registration_closed'])
                ->orderBy('starts_at')->paginate(12),
            'routePrefix' => 'events.show',
            'nameField' => 'title',
        ]);
    }

    public function show(string $locale, string $slug): View
    {
        return view('public.event', [
            'event' => Event::query()->where(compact('locale', 'slug'))
                ->whereNotIn('status', ['draft', 'cancelled'])->firstOrFail(),
        ]);
    }
}
