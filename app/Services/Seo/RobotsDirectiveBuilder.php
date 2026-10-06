<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Enums\Seo\RobotsDirective;

final readonly class RobotsDirectiveBuilder
{
    public function __construct(private SeoSettings $settings) {}

    /**
     * Directive actually sent. Outside production, or with indexing switched
     * off, every response is noindex, nofollow regardless of content state.
     */
    public function effective(RobotsDirective $intended): RobotsDirective
    {
        return $this->settings->indexingAllowed() ? $intended : RobotsDirective::NoindexNofollow;
    }

    /**
     * Directive the page would carry in production. An editor override can
     * only make an indexable page stricter; it can never expose a draft,
     * preview, search or private response.
     */
    public function intended(RobotsDirective $requested, ?string $override = null): RobotsDirective
    {
        if ($requested !== RobotsDirective::IndexFollow) {
            return $requested;
        }

        return RobotsDirective::parse($override) ?? $requested;
    }
}
