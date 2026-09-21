<?php

declare(strict_types=1);

use App\Models\Redirect;

it('renders canonical and locale alternate metadata and noindexes search', function (): void {
    $this->get('/en')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="http://localhost/en">', false)
        ->assertSee('hreflang="am"', false);

    $this->get('/en/search')
        ->assertOk()
        ->assertSee('content="noindex,follow"', false);
});

it('publishes segmented sitemaps and robots policy', function (): void {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('/sitemaps/en.xml', false);
    $this->get('/sitemaps/en.xml')->assertOk()->assertSee('/en/services', false);
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin');
});

it('applies approved permanent redirects and rejects redirect loops', function (): void {
    Redirect::query()->create([
        'source_path' => '/en/old-service',
        'destination_url' => '/en/services',
        'status_code' => 301,
        'enabled' => true,
    ]);
    $this->get('/en/old-service')->assertRedirect('/en/services')->assertStatus(301);

    Redirect::query()->create([
        'source_path' => '/en/loop',
        'destination_url' => '/en/loop',
        'status_code' => 301,
        'enabled' => true,
    ]);
    $this->get('/en/loop')->assertStatus(508);
});
