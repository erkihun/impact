<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final class AddSecurityHeaders
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        // A per-request nonce lets Vite-emitted inline scripts (the React
        // refresh preamble in development) run without 'unsafe-inline'.
        $nonce = Vite::useCspNonce();

        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-site');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        if ($request->is(
            'admin',
            'admin/*',
            'dashboard',
            'profile',
            'profile/*',
            'mfa',
            'mfa/*',
            'restricted-media/*',
            'application-files/*',
        )) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $dev = $this->viteDevServerSources();

        return "default-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; object-src 'none'; "
            ."img-src 'self' data:{$dev['http']}; font-src 'self'{$dev['http']}; "
            ."style-src 'self' 'unsafe-inline'{$dev['http']}; script-src 'self' 'nonce-{$nonce}'{$dev['http']}; "
            ."connect-src 'self'{$dev['http']}{$dev['ws']}";
    }

    /**
     * While `npm run dev` is running locally, assets are served from the Vite
     * dev server's origin, which 'self' does not cover. Allow that origin (and
     * its HMR websocket) in the local environment only.
     *
     * @return array{http: string, ws: string}
     */
    private function viteDevServerSources(): array
    {
        $none = ['http' => '', 'ws' => ''];

        if (! app()->environment('local') || ! Vite::isRunningHot()) {
            return $none;
        }

        $origin = rtrim(trim((string) @file_get_contents(Vite::hotFile())), '/');

        if (preg_match('#^https?://[^\s;\'"]+$#', $origin) !== 1) {
            return $none;
        }

        return [
            'http' => ' '.$origin,
            'ws' => ' '.preg_replace('#^http#', 'ws', $origin),
        ];
    }
}
