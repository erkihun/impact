<?php

declare(strict_types=1);

namespace App\Notifications\Newsletter;

use App\Support\Settings\NotificationDeliverySettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class ConfirmNewsletterSubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $confirmationToken,
        public readonly string $subscriptionLocale,
    ) {
        $delivery = app(NotificationDeliverySettings::class);
        $this->tries = $delivery->retryAttempts();
        $this->afterCommit();
        $this->onQueue($delivery->queue());
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return app(NotificationDeliverySettings::class)
            ->channels('notifications.newsletter_confirmations');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'newsletter.confirm',
            now('UTC')->addHours((int) config('impact.newsletter.confirmation_ttl_hours', 48)),
            ['token' => $this->confirmationToken],
        );

        $message = (new MailMessage)
            ->subject(__('Confirm your newsletter subscription'))
            ->greeting(__('Confirm your subscription'))
            ->line(__('Use the button below to confirm that you want to receive our newsletter.'))
            ->action(__('Confirm subscription'), $url)
            ->line(__('If you did not request this, no action is required.'));

        return app(NotificationDeliverySettings::class)->decorateMail($message);
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return ['locale' => $this->subscriptionLocale];
    }
}
