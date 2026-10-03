<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('en');
        config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);
        if ($request->hasSession()) {
            $request->session()->put('locale', 'en');
        }

        return $next($request);
    }
}
