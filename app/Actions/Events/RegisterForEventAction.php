<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Contracts\AuditRecorder;
use App\Contracts\Clock;
use App\Data\Audit\AuditData;
use App\Data\Events\RegisterForEventData;
use App\Enums\ConsentCategory;
use App\Models\ConsentRecord;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Notifications\Events\EventRegistrationConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class RegisterForEventAction
{
    public function __construct(
        private AuditRecorder $audit,
        private Clock $clock,
    ) {}

    public function execute(RegisterForEventData $data): EventRegistration
    {
        return DB::transaction(function () use ($data): EventRegistration {
            $event = Event::query()->lockForUpdate()->findOrFail($data->eventId);
            $email = mb_strtolower($data->email);

            $existing = EventRegistration::query()
                ->where('event_id', $event->id)
                ->where('normalized_email', $email)
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            if (! $event->acceptsRegistrations()) {
                throw ValidationException::withMessages([
                    'event' => __('Registration is closed or the event is full.'),
                ]);
            }

            $consent = ConsentRecord::query()->create([
                'subject_type' => 'event_registration',
                'subject_key_hash' => hash('sha256', "{$event->id}:{$email}"),
                'category' => ConsentCategory::Necessary,
                'decision' => true,
                'policy_version' => $data->policyVersion,
                'source' => 'public.event_registration',
                'ip_hash' => $data->ipHash,
                'recorded_at' => $this->clock->now(),
            ]);

            if ($data->marketingConsent) {
                ConsentRecord::query()->create([
                    'subject_type' => 'event_registration',
                    'subject_key_hash' => hash('sha256', "{$event->id}:{$email}"),
                    'category' => ConsentCategory::Marketing,
                    'decision' => true,
                    'policy_version' => $data->policyVersion,
                    'source' => 'public.event_registration',
                    'ip_hash' => $data->ipHash,
                    'recorded_at' => $this->clock->now(),
                ]);
            }

            $registration = EventRegistration::query()->create([
                'event_id' => $event->id,
                'name' => $data->name,
                'email' => $email,
                'normalized_email' => $email,
                'status' => 'confirmed',
                'confirmation_token_hash' => hash('sha256', Str::random(64)),
                'consent_record_id' => $consent->id,
                'retention_until' => $this->clock->today()->addDays(
                    (int) config('impact.retention.event_registration_days', 365),
                ),
                'registered_at' => $this->clock->now(),
            ]);

            $this->audit->record(new AuditData(
                action: 'event.registration.created',
                auditableType: EventRegistration::class,
                auditableId: (string) $registration->id,
                actorId: null,
                correlationId: $data->correlationId,
                afterHash: hash('sha256', $registration->toJson()),
                metadata: ['event_id' => $event->id, 'locale' => $data->locale],
                ipHash: $data->ipHash,
            ));
            DB::afterCommit(static fn () => Notification::route('mail', $registration->email)
                ->notify((new EventRegistrationConfirmedNotification(
                    $registration->name,
                    $event->title,
                    $event->starts_at->timezone($event->timezone)->toDateTimeString(),
                    $event->timezone,
                ))->locale($data->locale)));

            return $registration;
        }, attempts: 3);
    }
}
