<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Services\Seo\RedirectResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Answers URLs from the retired /{locale}/... structure.
 *
 * /en/... moves permanently to the unprefixed URL of the same page. /am/...
 * moves only when the same resource has a published English page, and is
 * otherwise 410 Gone. Nothing is redirected to the homepage by default and
 * every answer is a single hop.
 */
final class LegacyLocaleController extends Controller
{
    public function __invoke(Request $request, RedirectResolver $resolver, ?string $path = null): RedirectResponse
    {
        $locale = (string) $request->segment(1);
        $resolution = $resolver->resolveLegacy($locale, (string) $path);

        if ($resolution->redirectId !== null) {
            Redirect::query()->whereKey($resolution->redirectId)->update(['last_hit_at' => now('UTC')]);
            Redirect::query()->whereKey($resolution->redirectId)->increment('hit_count');
        }
        abort_if($resolution->destination === null, $resolution->status);

        $query = $request->getQueryString();

        return redirect()->to(
            $resolution->destination.($query ? '?'.$query : ''),
            $resolution->status,
            ['X-Robots-Tag' => 'noindex'],
        );
    }
}
