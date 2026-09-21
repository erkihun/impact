<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Settings\EffectiveSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(EffectiveSettings::class);
        $supported = $settings->array('localization.enabled_locales');
        $default = $settings->string('localization.default_locale');
        $requested = $request->route('locale');
        if (is_string($requested) && ! in_array($requested, $supported, true)) {
            abort(404);
        }

        $sessionLocale = $request->hasSession() ? $request->session()->get('locale') : null;
        $locale = is_string($requested) && in_array($requested, $supported, true)
            ? $requested
            : ($sessionLocale ?? $default);

        if (! is_string($locale) || ! in_array($locale, $supported, true)) {
            $locale = $default;
        }

        app()->setLocale($locale);
        config([
            'app.locale' => $locale,
            'app.fallback_locale' => $settings->string('localization.fallback_locale'),
        ]);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}
