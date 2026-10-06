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

final class EventController extends Controller
{
    public function index(PublicResourceQuery $resources): Response
    {
        return PublicContent::collection([
            'type' => PublicResourceType::Event,
            'eyebrow' => __('Connect and learn'),
            'title' => __('Events and webinars'),
            'items' => $resources->listing(PublicResourceType::Event)
                ->whereIn('status', PublicResourceQuery::LISTED_EVENT_STATES)
                ->orderBy('starts_at')
                ->paginate(12),
            'description' => __('Join Impact Consulting events, webinars and learning sessions on strategy, institutional performance and evidence-led delivery.'),
        ]);
    }

    public function show(string $slug): Response|RedirectResponse
    {
        return PublicResourcePage::show(PublicResourceType::Event, $slug);
    }
}
