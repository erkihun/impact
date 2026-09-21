<?php

declare(strict_types=1);

namespace App\Actions\Newsletter;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Enums\NewsletterStatus;
use App\Models\NewsletterSubscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ConfirmNewsletterSubscriptionAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(string $token, string $correlationId): NewsletterSubscription
    {
        return DB::transaction(function () use ($token, $correlationId): NewsletterSubscription {
            $subscription = NewsletterSubscription::query()
                ->where('confirmation_token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            $confirmationSentAt = $subscription?->getRawOriginal('confirmation_sent_at');
            $expiresAt = now('UTC')->subHours((int) config('impact.newsletter.confirmation_ttl_hours', 48));

            if (
                $subscription === null
                || $subscription->getRawOriginal('status') !== NewsletterStatus::Pending->value
                || $confirmationSentAt === null
                || CarbonImmutable::parse((string) $confirmationSentAt)->isBefore($expiresAt)
            ) {
                throw ValidationException::withMessages([
                    'token' => __('This confirmation link is invalid or has expired.'),
                ]);
            }

            $subscription->update([
                'status' => NewsletterStatus::Confirmed,
                'confirmation_token_hash' => null,
                'confirmed_at' => now('UTC'),
                'unsubscribed_at' => null,
            ]);

            $this->audit->record(new AuditData(
                action: 'newsletter.subscription_confirmed',
                auditableType: NewsletterSubscription::class,
                auditableId: (string) $subscription->getKey(),
                actorId: null,
                correlationId: $correlationId,
                afterHash: hash('sha256', $subscription->toJson()),
                metadata: ['locale' => $subscription->locale],
            ));

            return $subscription;
        }, attempts: 3);
    }
}
