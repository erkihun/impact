<?php

declare(strict_types=1);

use App\Models\Redirect;
use App\Models\SeoMetadata;
use App\Services\Seo\SitemapBuilder;
use Illuminate\Support\Facades\Storage;

it('publishes an English-only sitemap index with child sitemaps of canonical URLs', function (): void {
    publishService('digital-transformation');
    publishExpert('jane-doe');

    $index = $this->get('/sitemap.xml')->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->getContent();
    expect($index)->toContain('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
        ->toContain('<loc>http://localhost/sitemaps/pages.xml</loc>')
        ->toContain('<loc>http://localhost/sitemaps/services.xml</loc>')
        ->not->toContain('<loc}>');

    expect(simplexml_load_string($index))->not->toBeFalse();
    $services = $this->get('/sitemaps/services.xml')->assertOk()->getContent();
    expect(simplexml_load_string($services))->not->toBeFalse()
        ->and($services)->toContain('<loc>http://localhost/services/digital-transformation</loc>')
        ->toContain('<lastmod>');
    $this->get('/sitemaps/pages.xml')->assertOk()->assertSee('<loc>http://localhost/services</loc>', false)
        ->assertDontSee('/search', false);
});

it('includes only published, indexable, non-redirecting resources', function (): void {
    publishService('published-service', 'Published');
    publishService('draft-service', 'Draft', ['workflow_state' => 'draft']);
    $archived = publishService('archived-service', 'Archived');
    $archived->service->update(['status' => 'archived']);
    $excluded = publishService('excluded-service', 'Excluded');
    SeoMetadata::query()->create(['subject_type' => 'service', 'subject_key' => $excluded->service_id, 'robots' => 'noindex, follow', 'include_in_sitemap' => true]);
    publishService('moved-service', 'Moved');
    Redirect::query()->create(['source_path' => '/services/moved-service', 'destination_url' => '/services/published-service', 'status_code' => 301, 'enabled' => true]);
    Redirect::query()->create(['source_path' => '/services/gone-service', 'destination_url' => null, 'status_code' => 410, 'enabled' => true]);
    publishVacancy('closed-role', 'closed');

    $locs = app(SitemapBuilder::class)->entries('services')->pluck('loc')->all();

    expect($locs)->toBe(['http://localhost/services/published-service'])
        ->and(app(SitemapBuilder::class)->entries('careers')->all())->toBe([]);
});

it('writes sitemap files atomically when content is published and drops them when it is archived', function (): void {
    Storage::fake('local');
    $directory = config('impact.seo.sitemap_directory');
    $service = publishService('digital-transformation');

    Storage::disk('local')->assertExists("{$directory}/services.xml");
    expect(Storage::disk('local')->get("{$directory}/services.xml"))->toContain('/services/digital-transformation')
        ->and(collect(Storage::disk('local')->allFiles($directory))->filter(fn (string $file): bool => str_ends_with($file, '.tmp')))->toBeEmpty();

    $service->service->update(['status' => 'archived']);

    Storage::disk('local')->assertMissing("{$directory}/services.xml");
    $this->get('/services/digital-transformation')->assertStatus(410);
});

it('serves a robots.txt that blocks everything outside production', function (): void {
    $this->get('/robots.txt')->assertOk()->assertSee("User-agent: *\nDisallow: /", false)->assertDontSee('Sitemap:');
    expect(file_exists(public_path('robots.txt')))->toBeFalse();
});

it('serves a production robots.txt that allows public pages and references the sitemap', function (): void {
    asProduction();

    $this->get('https://localhost/robots.txt')->assertOk()
        ->assertSee("User-agent: *\nAllow: /", false)
        ->assertSee('Disallow: /admin', false)
        ->assertSee('Sitemap: https://localhost/sitemap.xml', false)
        ->assertDontSee("Disallow: /\n", false)
        ->assertDontSee('Disallow: /search', false);
});
