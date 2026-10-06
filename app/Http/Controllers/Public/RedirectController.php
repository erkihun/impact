<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Services\Seo\CanonicalUrlBuilder;
use App\Services\Seo\RedirectResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Fallback for every unmatched URL: governed redirects (single hop, local
 * destinations only), 410 Gone decisions, and case normalization. Anything
 * else is a 404.
 */
final class RedirectController extends Controller
{
    public function __invoke(Request $request, RedirectResolver $resolver, CanonicalUrlBuilder $urls): RedirectResponse
    {
        abort_unless($request->isMethod('GET') || $request->isMethod('HEAD'), 404);

        $raw = '/'.ltrim(rawurldecode($request->getPathInfo()), '/');
        $resolution = $resolver->resolve($raw);
        if ($resolution !== null) {
            if ($resolution->redirectId !== null) {
                Redirect::query()->whereKey($resolution->redirectId)->update(['last_hit_at' => now('UTC')]);
                Redirect::query()->whereKey($resolution->redirectId)->increment('hit_count');
            }
            abort_if($resolution->isGone() || $resolution->destination === null, 410);

            return $this->redirect($request, $resolution->destination, $resolution->status);
        }

        // /Services/Strategy → /services/strategy, only when that URL exists.
        $normalized = $urls->normalizePath($raw);
        if ($normalized !== $raw && $resolver->servesContent($normalized)) {
            return $this->redirect($request, $normalized, 301);
        }

        abort(404);
    }

    private function redirect(Request $request, string $destination, int $status): RedirectResponse
    {
        $query = $request->getQueryString();

        return redirect()->to($destination.($query ? '?'.$query : ''), $status);
    }
}
