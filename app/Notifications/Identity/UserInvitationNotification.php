<?php

declare(strict_types=1);

namespace App\Notifications\Identity;

use App\Support\Settings\NotificationDeliverySettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $token,
        public readonly \DateTimeInterface $expiresAt,
    ) {
        $delivery = app(NotificationDeliverySettings::class);
        $this->tries = $delivery->retryAttempts();
        $this->onQueue($delivery->queue());
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return app(NotificationDeliverySettings::class)->channels(mandatory: true);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('Your Impact Consulting account invitation'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('You have been invited to the Impact Consulting administration platform.'))
            ->action(__('Accept invitation'), route('invitation.show', ['token' => $this->token]))
            ->line(__('This invitation expires at :time.', [
                'time' => $this->expiresAt->format(\DateTimeInterface::ATOM),
            ]));

        return app(NotificationDeliverySettings::class)->decorateMail($message);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
