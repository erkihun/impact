<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Event;
use App\Models\ServiceVersion;
use Inertia\Testing\AssertableInertia as Assert;

it('declares English on every public page without language alternates', function (string $path): void {
    publishService('digital-transformation');

    $html = $this->get($path)->assertOk()->getContent();

    expect($html)
        ->toContain('<html lang="en" dir="ltr">')
        ->not->toContain('hreflang')
        ->not->toContain('x-default')
        ->not->toContain('og:locale:alternate');
})->with(['/', '/about', '/services', '/services/digital-transformation', '/contact', '/privacy', '/search']);

it('has no Amharic navigation, language switch or locale props', function (): void {
    $this->get('/about')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->missing('site.locale')
        ->where('navigation.primary.0.href', url('/'))
        ->where('navigation.mobile.2.href', url('/services')));

    expect(json_encode($this->get('/about', ['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()) ?? ''])->json('props.navigation')))
        ->not->toContain('\/am')
        ->not->toContain('\/en\/');
});

it('serves no Amharic or per-language sitemap', function (): void {
    $this->get('/sitemaps/am.xml')->assertNotFound();
    $this->get('/sitemaps/en.xml')->assertNotFound();
    expect($this->get('/sitemap.xml')->assertOk()->getContent())
        ->not->toContain('/am')
        ->not->toContain('/sitemaps/en.xml');
});

it('answers retired Amharic URLs with a single-hop 301 to the equivalent English page, otherwise 410', function (): void {
    $english = publishService('digital-transformation');
    ServiceVersion::query()->create([
        ...$english->only(['service_id', 'name', 'summary', 'deliverables']),
        'locale' => 'am', 'version_no' => 1, 'slug' => 'digital-transformation-am', 'workflow_state' => 'published',
    ]);
    $englishEvent = publishEvent('leadership-systems');
    Event::query()->create([
        ...$englishEvent->only(['status', 'format', 'description', 'starts_at', 'ends_at', 'timezone']),
        'title' => 'Amharic title', 'slug' => 'leadership-systems-am', 'locale' => 'am',
    ]);
    $orphan = publishService('retired-service');
    ServiceVersion::query()->create([
        ...$orphan->only(['service_id', 'name', 'deliverables']),
        'locale' => 'am', 'version_no' => 1, 'slug' => 'only-in-amharic', 'workflow_state' => 'published',
    ]);
    $orphan->service->update(['status' => 'archived']);

    (require database_path('migrations/2026_10_06_090100_migrate_legacy_locale_urls_to_english_only.php'))->up();

    $this->get('/am/services/digital-transformation-am')->assertStatus(301)->assertRedirect('/services/digital-transformation');
    $this->get('/am/events/leadership-systems-am')->assertStatus(301)->assertRedirect('/events/leadership-systems');
    $this->get('/am/services/only-in-amharic')->assertStatus(410);
    $this->get('/am/services/never-existed')->assertStatus(410);
    $this->get('/am')->assertStatus(301)->assertRedirect('/');
    $this->get('/am/about')->assertStatus(301)->assertRedirect('/about');
    // Never to the homepage as a catch-all.
    $this->get('/am/some/retired/page')->assertStatus(410);
});
