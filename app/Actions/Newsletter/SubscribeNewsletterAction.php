<?php

declare(strict_types=1);

namespace App\Actions\Newsletter;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Newsletter\SubscribeNewsletterData;
use App\Enums\ConsentCategory;
use App\Enums\NewsletterStatus;
use App\Models\ConsentRecord;
use App\Models\NewsletterSubscription;
use App\Notifications\Newsletter\ConfirmNewsletterSubscriptionNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final readonly class SubscribeNewsletterAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(SubscribeNewsletterData $data): NewsletterSubscription
    {
        $confirmationToken = Str::random(64);
        $unsubscribeToken = Str::random(64);
        $sendConfirmation = false;

        $subscription = DB::transaction(function () use (
            $data,
            $confirmationToken,
            $unsubscribeToken,
            &$sendConfirmation,
        ): NewsletterSubscription {
            $email = mb_strtolower(trim($data->email));
            $subscription = NewsletterSubscription::query()
                ->where('normalized_email', $email)
                ->where('locale', $data->locale)
                ->lockForUpdate()
                ->first();

            if ($subscription?->getRawOriginal('status') === NewsletterStatus::Suppressed->value) {
                return $subscription;
            }

            if ($subscription?->getRawOriginal('status') === NewsletterStatus::Confirmed->value) {
                return $subscription;
            }

            $consent = ConsentRecord::query()->create([
                'subject_type' => 'newsletter_subscription',
                'subject_key_hash' => hash('sha256', $email),
                'category' => ConsentCategory::Marketing,
                'decision' => true,
                'policy_version' => $data->policyVersion,
                'source' => $data->source,
                'ip_hash' => $data->ipHash,
                'recorded_at' => now('UTC'),
            ]);

            $values = [
                'email' => $email,
                'status' => NewsletterStatus::Pending,
                'confirmation_token_hash' => hash('sha256', $confirmationToken),
                'unsubscribe_token_hash' => hash('sha256', $unsubscribeToken),
                'confirmation_sent_at' => now('UTC'),
                'confirmed_at' => null,
                'unsubscribed_at' => null,
                'consent_record_id' => $consent->getKey(),
                'policy_version' => $data->policyVersion,
                'source' => $data->source,
            ];

            if ($subscription === null) {
                $subscription = NewsletterSubscription::query()->create([
                    'normalized_email' => $email,
                    'locale' => $data->locale,
                    ...$values,
                ]);
            } else {
                $subscription->update($values);
            }

            $sendConfirmation = true;

            $this->audit->record(new AuditData(
                action: 'newsletter.subscription_requested',
                auditableType: NewsletterSubscription::class,
                auditableId: (string) $subscription->getKey(),
                actorId: null,
                correlationId: $data->correlationId,
                afterHash: hash('sha256', $subscription->toJson()),
                metadata: [
                    'locale' => $data->locale,
                    'policy_version' => $data->policyVersion,
                ],
                ipHash: $data->ipHash,
            ));

            return $subscription;
        }, attempts: 3);

        if ($sendConfirmation) {
            DB::afterCommit(static fn () => Notification::route('mail', $subscription->email)
                ->notify((new ConfirmNewsletterSubscriptionNotification(
                    confirmationToken: $confirmationToken,
                    subscriptionLocale: $subscription->locale,
                ))->locale($subscription->locale)));
        }

        return $subscription;
    }
}
