<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Data\Seo\SitemapEntry;
use App\Http\Controllers\Controller;
use App\Services\Seo\CanonicalUrlBuilder;
use App\Services\Seo\SeoSettings;
use App\Services\Seo\SitemapBuilder;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;

/**
 * Serves the generated sitemap files (falling back to live generation when
 * a file has not been written yet) and the environment-aware robots.txt.
 */
final class SitemapController extends Controller
{
    public function index(SitemapBuilder $sitemaps, SeoSettings $settings): Response
    {
        abort_unless($settings->sitemapEnabled(), 404);

        $xml = $sitemaps->stored('index') ?? $sitemaps->indexXml(collect($sitemaps->all())
            ->reject(static fn ($entries): bool => $entries->isEmpty())
            ->map(static fn ($entries): ?CarbonInterface => $entries->map(static fn (SitemapEntry $entry): ?CarbonInterface => $entry->lastModified)->filter()->max())
            ->all());

        return $this->xml($xml);
    }

    public function segment(string $segment, SitemapBuilder $sitemaps, SeoSettings $settings): Response
    {
        abort_unless($settings->sitemapEnabled() && in_array($segment, $sitemaps->segments(), true), 404);

        $xml = $sitemaps->stored($segment);
        if ($xml === null) {
            $entries = $sitemaps->entries($segment);
            abort_if($entries->isEmpty(), 404);
            $xml = $sitemaps->urlsetXml($entries);
        }

        return $this->xml($xml);
    }

    public function robots(SeoSettings $settings, CanonicalUrlBuilder $urls): Response
    {
        if (! $settings->indexingAllowed()) {
            // Staging, development and any production instance with indexing
            // switched off must never be crawled.
            return $this->text("User-agent: *\nDisallow: /\n");
        }

        $lines = [
            'User-agent: *',
            'Allow: /',
            // Authentication protects these; robots.txt only saves crawl budget.
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /profile',
            'Disallow: /login',
            'Disallow: /mfa',
            'Disallow: /forgot-password',
            'Disallow: /reset-password/',
            'Disallow: /invitations/',
            'Disallow: /newsletter/',
            'Disallow: /restricted-media/',
            'Disallow: /application-files/',
            'Disallow: /submission-files/',
        ];
        if ($settings->sitemapEnabled()) {
            $lines[] = '';
            $lines[] = 'Sitemap: '.$urls->forRoute('sitemap.index');
        }

        return $this->text(implode("\n", $lines)."\n");
    }

    private function xml(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }

    private function text(string $body): Response
    {
        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
