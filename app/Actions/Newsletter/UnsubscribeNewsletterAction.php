<?php

declare(strict_types=1);

namespace App\Actions\Newsletter;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Enums\ConsentCategory;
use App\Enums\NewsletterStatus;
use App\Models\ConsentRecord;
use App\Models\NewsletterSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UnsubscribeNewsletterAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(string $token, string $correlationId): NewsletterSubscription
    {
        return DB::transaction(function () use ($token, $correlationId): NewsletterSubscription {
            $subscription = NewsletterSubscription::query()
                ->where('unsubscribe_token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                throw ValidationException::withMessages([
                    'token' => __('This unsubscribe link is invalid.'),
                ]);
            }

            if ($subscription->getRawOriginal('status') === NewsletterStatus::Suppressed->value) {
                return $subscription;
            }

            if ($subscription->getRawOriginal('status') !== NewsletterStatus::Unsubscribed->value) {
                $subscription->update([
                    'status' => NewsletterStatus::Unsubscribed,
                    'confirmation_token_hash' => null,
                    'unsubscribed_at' => now('UTC'),
                ]);

                ConsentRecord::query()->create([
                    'subject_type' => 'newsletter_subscription',
                    'subject_key_hash' => hash('sha256', $subscription->normalized_email),
                    'category' => ConsentCategory::Marketing,
                    'decision' => false,
                    'policy_version' => $subscription->policy_version,
                    'source' => 'newsletter.unsubscribe',
                    'recorded_at' => now('UTC'),
                ]);

                $this->audit->record(new AuditData(
                    action: 'newsletter.subscription_unsubscribed',
                    auditableType: NewsletterSubscription::class,
                    auditableId: (string) $subscription->getKey(),
                    actorId: null,
                    correlationId: $correlationId,
                    afterHash: hash('sha256', $subscription->toJson()),
                    metadata: ['locale' => $subscription->locale],
                ));
            }

            return $subscription;
        }, attempts: 3);
    }
}
