<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Settings\EffectiveSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureMfaVerified
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_if($user === null, 401);

        if ($user->requiresMfa()) {
            $settings = app(EffectiveSettings::class);
            if (! $settings->boolean('authentication.require_mfa_privileged')) {
                return $next($request);
            }

            $verifiedAt = $request->session()->get('mfa_verified_at');
            if (! is_int($verifiedAt)
                || $verifiedAt < now('UTC')
                    ->subMinutes($settings->integer('authentication.absolute_session_lifetime_minutes'))
                    ->timestamp) {
                return redirect()->guest(route('mfa.show'));
            }
        }

        return $next($request);
    }
}
