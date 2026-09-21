<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CaseStudyVersion;
use App\Models\IndustryVersion;
use App\Models\InsightVersion;
use App\Models\ServiceVersion;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

final class SitemapController extends Controller
{
    public function index(EffectiveSettings $settings): Response
    {
        abort_unless($settings->boolean('seo.sitemap_enabled'), 404);

        $entries = collect($settings->array('localization.enabled_locales'))
            ->map(fn (string $locale): string => url("/sitemaps/{$locale}.xml"));

        return $this->xml('sitemapindex', $entries, 'sitemap');
    }

    public function locale(string $locale, EffectiveSettings $settings): Response
    {
        abort_unless(
            $settings->boolean('seo.sitemap_enabled')
                && in_array($locale, $settings->array('localization.enabled_locales'), true),
            404,
        );

        $urls = collect([
            url("/{$locale}"),
            url("/{$locale}/about"),
            url("/{$locale}/services"),
            url("/{$locale}/industries"),
            url("/{$locale}/experts"),
            url("/{$locale}/case-studies"),
            url("/{$locale}/insights"),
            url("/{$locale}/events"),
            url("/{$locale}/careers"),
        ]);

        foreach ([
            [ServiceVersion::class, 'services'],
            [IndustryVersion::class, 'industries'],
            [CaseStudyVersion::class, 'case-studies'],
            [InsightVersion::class, 'insights'],
        ] as [$model, $segment]) {
            $model::query()
                ->where('locale', $locale)
                ->where('workflow_state', 'published')
                ->pluck('slug')
                ->each(fn (string $slug) => $urls->push(url("/{$locale}/{$segment}/{$slug}")));
        }

        return $this->xml('urlset', $urls->unique()->values(), 'url');
    }

    public function robots(EffectiveSettings $settings): Response
    {
        $publicPolicy = $settings->boolean('seo.robots_indexing') ? 'Allow: /' : 'Disallow: /';
        $sitemap = $settings->boolean('seo.sitemap_enabled')
            ? "\nSitemap: ".url('/sitemap.xml')
            : '';

        return response(
            "User-agent: *\n{$publicPolicy}\nDisallow: /admin\nDisallow: /dashboard\nDisallow: /*/search{$sitemap}\n",
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }

    /** @param Collection<int, string> $locations */
    private function xml(string $root, Collection $locations, string $node): Response
    {
        $body = $locations
            ->map(fn (string $location): string => "<{$node}><loc}>"
                .htmlspecialchars($location, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                ."</loc></{$node}>")
            ->implode('');

        return response(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?><{$root} xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">{$body}</{$root}>",
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }
}
