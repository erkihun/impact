<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Contracts\CdnPurger;
use Illuminate\Support\Facades\Log;

/**
 * Default purger. No CDN is configured for the platform yet, so affected
 * URLs are logged for operations; bind a provider adapter to CdnPurger when
 * a CDN is introduced.
 */
final class LogCdnPurger implements CdnPurger
{
    public function purge(array $urls): void
    {
        if ($urls !== []) {
            Log::info('seo.cdn_purge_requested', ['urls' => array_values(array_unique($urls))]);
        }
    }
}
