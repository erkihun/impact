<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Newsletter\ConfirmNewsletterSubscriptionAction;
use App\Actions\Newsletter\SubscribeNewsletterAction;
use App\Actions\Newsletter\UnsubscribeNewsletterAction;
use App\Data\Newsletter\SubscribeNewsletterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ConfirmNewsletterRequest;
use App\Http\Requests\Public\SubscribeNewsletterRequest;
use App\Support\CorrelationContext;
use Illuminate\Http\RedirectResponse;

final class NewsletterSubscriptionController extends Controller
{
    public function store(
        SubscribeNewsletterRequest $request,
        SubscribeNewsletterAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $action->execute(new SubscribeNewsletterData(
            email: (string) $request->validated('email'),
            locale: app()->getLocale(),
            policyVersion: (string) $request->validated('policy_version'),
            ipHash: hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            correlationId: $correlation->id(),
        ));

        return back()->with('status', __('Check your email to confirm your subscription.'));
    }

    public function confirm(
        ConfirmNewsletterRequest $request,
        string $token,
        ConfirmNewsletterSubscriptionAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $subscription = $action->execute($token, $correlation->id());

        return redirect()
            ->route('localized-home', ['locale' => $subscription->locale])
            ->with('status', __('Your newsletter subscription is confirmed.'));
    }

    public function unsubscribe(
        ConfirmNewsletterRequest $request,
        string $token,
        UnsubscribeNewsletterAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $subscription = $action->execute($token, $correlation->id());

        return redirect()
            ->route('localized-home', ['locale' => $subscription->locale])
            ->with('status', __('You have been unsubscribed.'));
    }
}
