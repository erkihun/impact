<?php

declare(strict_types=1);

namespace App\Support\Settings;

use Illuminate\Notifications\Messages\MailMessage;

final readonly class NotificationDeliverySettings
{
    public function __construct(private EffectiveSettings $settings) {}

    /** @return list<string> */
    public function channels(?string $eventToggle = null, bool $mandatory = false): array
    {
        if (! $this->settings->boolean('email.enabled')
            || ! $this->settings->boolean('notifications.email_enabled')) {
            return [];
        }

        if (! $mandatory && $eventToggle !== null && ! $this->settings->boolean($eventToggle)) {
            return [];
        }

        return ['mail'];
    }

    public function queue(): string
    {
        return $this->settings->string('email.queue_name');
    }

    public function retryAttempts(): int
    {
        return $this->settings->integer('email.retry_attempts');
    }

    public function decorateMail(MailMessage $message): MailMessage
    {
        $message->from(
            $this->settings->string('email.from_address'),
            $this->settings->string('email.from_name'),
        );

        if (($replyTo = $this->settings->nullableString('email.reply_to')) !== null) {
            $message->replyTo($replyTo);
        }

        if (($footer = $this->settings->nullableString('email.footer_text')) !== null) {
            $message->line($footer);
        }

        return $message;
    }
}
