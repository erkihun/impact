<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\SettingCatalog;
use App\Support\Settings\EffectiveSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureRecentMfa
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, int|string $minutes = 15): Response
    {
        if ($request->user()?->mfa_exempt === true) {
            return $next($request);
        }

        $category = (string) $request->route('category', '');

        if (! SettingCatalog::categoryRequiresRecentMfa($category)) {
            return $next($request);
        }

        $settings = app(EffectiveSettings::class);
        if (! $settings->boolean('security.require_recent_mfa_high_risk')) {
            return $next($request);
        }

        $validMinutes = min(
            (int) $minutes,
            $settings->integer('security.recent_mfa_valid_minutes'),
        );
        $verifiedAt = $request->session()->get('mfa_verified_at');
        if (! is_int($verifiedAt) || $verifiedAt < now('UTC')->subMinutes($validMinutes)->timestamp) {
            return redirect()
                ->route('mfa.show')
                ->with('status', __('Confirm MFA again before changing sensitive settings.'));
        }

        return $next($request);
    }
}
