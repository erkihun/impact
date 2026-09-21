<?php

declare(strict_types=1);

use App\Enums\NewsletterStatus;
use App\Models\AuditEvent;
use App\Models\ConsentRecord;
use App\Models\NewsletterSubscription;
use App\Notifications\Newsletter\ConfirmNewsletterSubscriptionNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('requires marketing consent and confirms a subscription through a signed single-use link', function (): void {
    Notification::fake();

    $this->post(route('newsletter.subscribe', ['locale' => 'en']), [
        'email' => 'Reader@Example.com',
        'policy_version' => config('impact.privacy.policy_version'),
    ])->assertSessionHasErrors('marketing_consent');

    $this->post(route('newsletter.subscribe', ['locale' => 'en']), [
        'email' => 'Reader@Example.com',
        'marketing_consent' => '1',
        'policy_version' => config('impact.privacy.policy_version'),
    ])->assertSessionHas('status');

    $subscription = NewsletterSubscription::query()->sole();

    expect($subscription->normalized_email)->toBe('reader@example.com')
        ->and($subscription->status)->toBe(NewsletterStatus::Pending)
        ->and($subscription->consentRecord)->not->toBeNull()
        ->and(ConsentRecord::query()->where('decision', true)->count())->toBe(1);

    $token = null;
    Notification::assertSentOnDemand(
        ConfirmNewsletterSubscriptionNotification::class,
        function (ConfirmNewsletterSubscriptionNotification $notification) use (&$token): bool {
            $token = $notification->confirmationToken;

            return $notification->subscriptionLocale === 'en';
        },
    );

    expect($token)->toBeString();
    $confirmationUrl = URL::temporarySignedRoute(
        'newsletter.confirm',
        now()->addHour(),
        ['token' => $token],
    );

    $this->get($confirmationUrl)
        ->assertRedirect(route('localized-home', ['locale' => 'en']));

    expect($subscription->refresh()->status)->toBe(NewsletterStatus::Confirmed)
        ->and($subscription->confirmation_token_hash)->toBeNull()
        ->and(AuditEvent::query()->where('action', 'newsletter.subscription_confirmed')->exists())->toBeTrue();

    $this->get($confirmationUrl)->assertSessionHasErrors('token');
});

it('supports idempotent one-click unsubscribe and suppresses future marketing', function (): void {
    $token = 'unsubscribe-test-token';
    $subscription = NewsletterSubscription::query()->create([
        'email' => 'reader@example.com',
        'normalized_email' => 'reader@example.com',
        'locale' => 'am',
        'policy_version' => config('impact.privacy.policy_version'),
        'status' => NewsletterStatus::Confirmed,
        'unsubscribe_token_hash' => hash('sha256', $token),
        'confirmed_at' => now(),
        'source' => 'test',
    ]);
    $unsubscribeUrl = URL::temporarySignedRoute(
        'newsletter.unsubscribe',
        now()->addHour(),
        ['token' => $token],
    );

    $this->get($unsubscribeUrl)
        ->assertRedirect(route('localized-home', ['locale' => 'am']));
    $this->get($unsubscribeUrl)
        ->assertRedirect(route('localized-home', ['locale' => 'am']));

    expect($subscription->refresh()->status)->toBe(NewsletterStatus::Unsubscribed)
        ->and($subscription->unsubscribed_at)->not->toBeNull()
        ->and(ConsentRecord::query()->where('decision', false)->count())->toBe(1)
        ->and(AuditEvent::query()->where('action', 'newsletter.subscription_unsubscribed')->count())->toBe(1);
});

it('rejects unsigned newsletter lifecycle links', function (): void {
    $this->get(route('newsletter.confirm', ['token' => 'invalid']))->assertForbidden();
    $this->get(route('newsletter.unsubscribe', ['token' => 'invalid']))->assertForbidden();
});
