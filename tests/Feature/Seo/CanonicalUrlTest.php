<?php

declare(strict_types=1);

use App\Models\Redirect;
use App\Models\ServiceVersion;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

it('has exactly one indexable URL per resource: retired /en URLs redirect permanently in one hop', function (): void {
    publishService('digital-transformation');

    $this->get('/en')->assertStatus(301)->assertRedirect('/');
    $this->get('/en/services')->assertStatus(301)->assertRedirect('/services');
    $this->get('/en/services/digital-transformation')->assertStatus(301)->assertRedirect('/services/digital-transformation');
    $this->get('/en/search?q=strategy')->assertStatus(301)->assertRedirect('/search?q=strategy');
    $this->get('/en/services/does-not-exist')->assertNotFound();
});

it('resolves a retired /en URL straight to the final destination of a managed redirect', function (): void {
    publishService('digital-transformation');
    Redirect::query()->create(['source_path' => '/old-service', 'destination_url' => '/services/digital-transformation', 'status_code' => 301, 'enabled' => true]);

    $this->get('/en/old-service')->assertStatus(301)->assertRedirect('/services/digital-transformation');
});

it('normalizes trailing slashes and letter case to the canonical URL', function (): void {
    publishService('digital-transformation');

    // The test client trims trailing slashes, so send the raw request.
    $response = app(Kernel::class)->handle(Request::create('http://localhost/services/?page=1'));
    expect($response->getStatusCode())->toBe(301)
        ->and($response->headers->get('Location'))->toBe('http://localhost/services?page=1');
    $this->get('/Services/Digital-Transformation')->assertStatus(301)->assertRedirect('/services/digital-transformation');
});

it('strips tracking parameters from the canonical and noindexes arbitrary filter combinations', function (): void {
    asProduction();
    publishService('digital-transformation');

    $this->get('https://localhost/services?utm_source=newsletter')
        ->assertSee('rel="canonical" href="https://localhost/services"', false)
        ->assertSee('name="robots" content="index, follow"', false);

    $this->get('https://localhost/services?industry=public-sector&sort=date')
        ->assertSee('rel="canonical" href="https://localhost/services"', false)
        ->assertSee('name="robots" content="noindex, follow"', false);

    $this->get('https://localhost/services/digital-transformation?ref=x')
        ->assertSee('rel="canonical" href="https://localhost/services/digital-transformation"', false);
});

it('gives real paginated listing pages their own canonical, and 404s beyond the last page', function (): void {
    asProduction();
    foreach (range(1, 13) as $index) {
        publishService("service-{$index}", "Service {$index}");
    }

    $this->get('https://localhost/services?page=2')
        ->assertOk()
        ->assertSee('rel="canonical" href="https://localhost/services?page=2"', false)
        ->assertSee('Consulting services – Page 2 | Impact Consulting', false);
    $this->get('https://localhost/services?page=1')
        ->assertSee('rel="canonical" href="https://localhost/services"', false);
    $this->get('https://localhost/services?page=9')->assertNotFound();
});

it('permanently redirects an older published version slug to the current one', function (): void {
    $first = publishService('digital-transformation');
    ServiceVersion::query()->create([
        ...$first->only(['service_id', 'name', 'summary', 'deliverables']),
        'locale' => 'en', 'version_no' => 2, 'slug' => 'digital-government', 'workflow_state' => 'published',
    ]);

    $this->get('/services/digital-transformation')->assertStatus(301)->assertRedirect('/services/digital-government');
    $this->get('/services/digital-government')->assertOk();
});

it('keeps the live URL when a draft revision proposes a different slug', function (): void {
    $published = publishService('digital-transformation');
    ServiceVersion::query()->create([
        ...$published->only(['service_id', 'name', 'summary', 'deliverables']),
        'locale' => 'en', 'version_no' => 2, 'slug' => 'draft-slug', 'workflow_state' => 'draft',
    ]);

    $this->get('/services/digital-transformation')->assertOk();
    $this->get('/services/draft-slug')->assertNotFound();
    expect(Redirect::query()->where('source_path', '/services/digital-transformation')->exists())->toBeFalse();
});
