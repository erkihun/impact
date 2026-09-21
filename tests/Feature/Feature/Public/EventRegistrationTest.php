<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\EventRegistration;

it('registers once for an open event and records consent and audit evidence', function (): void {
    $event = Event::query()->create([
        'status' => 'registration_open',
        'format' => 'online',
        'title' => 'Delivery systems',
        'slug' => 'delivery-systems',
        'locale' => 'en',
        'description' => 'A practical event.',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHour(),
        'timezone' => 'Africa/Addis_Ababa',
        'capacity' => 2,
        'registration_closes_at' => now()->addDays(6),
    ]);
    $payload = [
        'name' => 'Aster Bekele',
        'email' => 'ASTER@example.com',
        'privacy_acknowledged' => '1',
        'marketing_consent' => '1',
    ];

    $this->post("/en/events/{$event->slug}/registrations", $payload)
        ->assertRedirect()
        ->assertSessionHas('status');
    $this->post("/en/events/{$event->slug}/registrations", $payload)->assertRedirect();

    expect(EventRegistration::query()->count())->toBe(1)
        ->and(EventRegistration::query()->sole()->normalized_email)->toBe('aster@example.com');
    $this->assertDatabaseCount('consent_records', 2);
    $this->assertDatabaseHas('audit_events', ['action' => 'event.registration.created']);
});

it('rejects a registration when capacity is exhausted', function (): void {
    $event = Event::query()->create([
        'status' => 'registration_open',
        'format' => 'physical',
        'title' => 'Full event',
        'slug' => 'full-event',
        'locale' => 'en',
        'description' => 'A full event.',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHour(),
        'timezone' => 'Africa/Addis_Ababa',
        'capacity' => 1,
    ]);
    EventRegistration::query()->create([
        'event_id' => $event->id,
        'name' => 'Existing',
        'email' => 'existing@example.com',
        'normalized_email' => 'existing@example.com',
        'status' => 'confirmed',
    ]);

    $this->from("/en/events/{$event->slug}")
        ->post("/en/events/{$event->slug}/registrations", [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'privacy_acknowledged' => '1',
        ])
        ->assertRedirect("/en/events/{$event->slug}")
        ->assertSessionHasErrors('event');
});
