<?php

declare(strict_types=1);

namespace App\Notifications\Recruitment;

use App\Support\Settings\NotificationDeliverySettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ApplicationReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly string $applicantName,
        public readonly string $reference,
        public readonly string $vacancyTitle,
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
        return app(NotificationDeliverySettings::class)
            ->channels('notifications.application_submissions');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('Application received'))
            ->greeting(__('Hello :name,', ['name' => $this->applicantName]))
            ->line(__('We received your application for :vacancy.', ['vacancy' => $this->vacancyTitle]))
            ->line(__('Reference: :reference', ['reference' => $this->reference]))
            ->line(__('We will contact you if your application progresses.'));

        return app(NotificationDeliverySettings::class)->decorateMail($message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['reference' => $this->reference];
    }
}
