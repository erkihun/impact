<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Seo\SeoSettings;
use App\Services\Seo\SitemapBuilder;
use Illuminate\Console\Command;

final class SeoSitemapGenerateCommand extends Command
{
    protected $signature = 'seo:sitemap-generate';

    protected $description = 'Regenerate the English-only XML sitemap index and child sitemaps (atomic, locked)';

    public function handle(SitemapBuilder $sitemaps, SeoSettings $settings): int
    {
        if (! $settings->sitemapEnabled()) {
            $this->components->warn('The sitemap is disabled in SEO settings; nothing generated.');

            return self::SUCCESS;
        }

        $counts = $sitemaps->generate();
        if ($counts === []) {
            $this->components->warn('No public URLs found, or another process holds the generation lock.');

            return self::SUCCESS;
        }

        foreach ($counts as $segment => $count) {
            $this->components->twoColumnDetail("/sitemaps/{$segment}.xml", "{$count} URL(s)");
        }
        $this->components->info('Sitemap index: '.route('sitemap.index').' ('.array_sum($counts).' URLs, host '.$settings->canonicalBaseUrl().').');

        return self::SUCCESS;
    }
}
