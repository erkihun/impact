<?php

declare(strict_types=1);

namespace App\Notifications\Engagement;

use App\Support\Settings\NotificationDeliverySettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EngagementReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly string $contactName,
        public readonly string $reference,
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
            ->channels('notifications.engagement_submissions');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('We received your request'))
            ->greeting(__('Hello :name,', ['name' => $this->contactName]))
            ->line(__('Thank you. Your request has been received and will be reviewed by the appropriate team.'))
            ->line(__('Reference: :reference', ['reference' => $this->reference]))
            ->line(__('Keep this reference for future correspondence.'));

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
