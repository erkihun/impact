<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * One URL form: no trailing slash. Laravel would otherwise answer both
 * /services and /services/ with the same page.
 */
final class NormalizeTrailingSlash
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        if (in_array($request->getMethod(), ['GET', 'HEAD'], true) && $path !== '/' && str_ends_with($path, '/')) {
            $query = $request->getQueryString();

            return redirect()->to(rtrim($path, '/').($query ? '?'.$query : ''), 301);
        }

        return $next($request);
    }
}
