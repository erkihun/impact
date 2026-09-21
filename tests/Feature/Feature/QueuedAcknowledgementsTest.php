<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Vacancy;
use App\Notifications\Engagement\EngagementReceivedNotification;
use App\Notifications\Events\EventRegistrationConfirmedNotification;
use App\Notifications\Recruitment\ApplicationReceivedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('queues localized on-demand acknowledgements after committed public submissions', function (): void {
    Notification::fake();
    Storage::fake('local');

    $this->post('/en/consultation-requests', [
        'type' => 'consultation',
        'contact_name' => 'Aster Bekele',
        'email' => 'aster@example.com',
        'description' => 'A complete request for institutional transformation support.',
        'privacy_acknowledged' => '1',
        'policy_version' => '2026-07-26',
    ])->assertRedirect();

    $event = Event::query()->create([
        'status' => 'registration_open',
        'format' => 'online',
        'title' => 'Delivery systems',
        'slug' => 'delivery-systems-ack',
        'locale' => 'en',
        'description' => 'A practical event.',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHour(),
        'timezone' => 'Africa/Addis_Ababa',
        'capacity' => 20,
    ]);
    $this->post("/en/events/{$event->slug}/registrations", [
        'name' => 'Aster Bekele',
        'email' => 'aster@example.com',
        'privacy_acknowledged' => '1',
    ])->assertRedirect();

    $vacancy = Vacancy::query()->create([
        'reference_no' => 'VAC-ACK',
        'status' => 'published',
        'title' => 'Consultant',
        'slug' => 'consultant-ack',
        'locale' => 'en',
        'type' => 'full_time',
        'description' => 'Role description',
        'requirements' => 'Role requirements',
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addWeek(),
    ]);
    $this->post("/en/careers/{$vacancy->slug}/applications", [
        'applicant_name' => 'Aster Bekele',
        'email' => 'aster@example.com',
        'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        'privacy_acknowledged' => '1',
    ])->assertRedirect();

    Notification::assertSentOnDemand(EngagementReceivedNotification::class);
    Notification::assertSentOnDemand(EventRegistrationConfirmedNotification::class);
    Notification::assertSentOnDemand(ApplicationReceivedNotification::class);
});
