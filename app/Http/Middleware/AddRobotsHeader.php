<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Seo\SeoSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header-level indexing control, covering responses that have no HTML head
 * (downloads, JSON, redirects).
 *
 * - Outside production, or with indexing disabled: every response is
 *   noindex, nofollow.
 * - In production: authenticated areas, private downloads and form posts are
 *   noindex, nofollow. Public pages rely on their meta robots tag.
 */
final class AddRobotsHeader
{
    private const PRIVATE_PATTERNS = [
        'admin', 'admin/*', 'dashboard', 'profile', 'login', 'logout', 'mfa', 'mfa/*', 'register',
        'forgot-password', 'reset-password', 'reset-password/*', 'confirm-password', 'verify-email', 'verify-email/*',
        'invitations/*', 'restricted-media/*', 'application-files/*', 'submission-files/*', 'newsletter/*', 'api/*',
    ];

    public function __construct(private readonly SeoSettings $settings) {}

    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->headers->has('X-Robots-Tag')) {
            return $response;
        }

        if (! $this->settings->indexingAllowed()
            || $request->is(...self::PRIVATE_PATTERNS)
            || ! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
