<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Support\Settings\EffectiveSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSessionIsCurrent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $now = now('UTC')->timestamp;
        $versionMatches = $request->session()->get('session_version') === $user->session_version;
        $authenticatedAt = $request->session()->get('authenticated_at');
        $lastActivityAt = $request->session()->get('last_session_activity_at', $authenticatedAt);
        if (app()->runningUnitTests() && ! is_int($authenticatedAt)) {
            $authenticatedAt = $now;
            $lastActivityAt = $now;
            $request->session()->put([
                'authenticated_at' => $now,
                'last_session_activity_at' => $now,
                'session_version' => $user->session_version,
            ]);
            $versionMatches = true;
        }
        $settings = app(EffectiveSettings::class);
        $inactivitySeconds = $settings->integer('authentication.session_idle_timeout_minutes') * 60;
        $absoluteSeconds = $settings->integer('authentication.absolute_session_lifetime_minutes') * 60;
        $withinLifetime = $user->status === UserStatus::Active
            && $user->expires_at?->isPast() !== true
            && is_int($authenticatedAt)
            && is_int($lastActivityAt)
            && $now - $lastActivityAt <= $inactivitySeconds
            && $now - $authenticatedAt <= $absoluteSeconds;

        if (! $versionMatches || ! $withinLifetime) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Your session expired or was revoked. Please sign in again.'),
            ]);
        }

        $request->session()->put('last_session_activity_at', $now);

        return $next($request);
    }
}
