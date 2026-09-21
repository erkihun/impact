<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Support\Settings\NotificationDeliverySettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EventRegistrationConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $eventTitle,
        public readonly string $startsAt,
        public readonly string $timezone,
    ) {
        $delivery = app(NotificationDeliverySettings::class);
        $this->tries = $delivery->retryAttempts();
        $this->afterCommit();
        $this->onQueue($delivery->queue());
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return app(NotificationDeliverySettings::class)->channels();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('Event registration confirmed'))
            ->greeting(__('Hello :name,', ['name' => $this->name]))
            ->line(__('Your registration for :event is confirmed.', ['event' => $this->eventTitle]))
            ->line(__('Start: :time (:timezone)', ['time' => $this->startsAt, 'timezone' => $this->timezone]))
            ->line(__('We look forward to welcoming you.'));

        return app(NotificationDeliverySettings::class)->decorateMail($message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['event' => $this->eventTitle];
    }
}
