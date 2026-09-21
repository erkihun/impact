<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Events\RegisterForEventAction;
use App\Data\Events\RegisterForEventData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\RegisterForEventRequest;
use App\Models\Event;
use App\Support\CorrelationContext;
use Illuminate\Http\RedirectResponse;

final class EventRegistrationController extends Controller
{
    public function store(
        RegisterForEventRequest $request,
        string $locale,
        string $slug,
        RegisterForEventAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $event = Event::query()->where(compact('locale', 'slug'))->firstOrFail();
        $validated = $request->validated();
        $registration = $action->execute(new RegisterForEventData(
            eventId: (string) $event->id,
            name: $validated['name'],
            email: $validated['email'],
            locale: $locale,
            policyVersion: (string) config('impact.privacy.policy_version'),
            correlationId: $correlation->id(),
            ipHash: hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            marketingConsent: $request->boolean('marketing_consent'),
        ));

        return back()->with('status', __(
            'Registration confirmed. Reference: :reference',
            ['reference' => $registration->id],
        ));
    }
}
