<?php

declare(strict_types=1);

namespace App\Jobs\Seo;

use App\Services\Seo\SeoSettings;
use App\Services\Seo\SitemapBuilder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Rebuilds the XML sitemaps after publication changes. Unique while queued,
 * so a burst of edits produces one rebuild; the builder also holds a lock
 * so concurrent workers never write at the same time.
 */
final class GenerateSitemapsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 120;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function handle(SitemapBuilder $sitemaps, SeoSettings $settings): void
    {
        if (! $settings->sitemapEnabled()) {
            return;
        }

        $sitemaps->generate();
    }
}
