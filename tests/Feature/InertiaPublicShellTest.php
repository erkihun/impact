<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

it('serves the homepage as a React page inside the shared public shell', function (): void {
    $this->get('/en')
        ->assertOk()
        ->assertSee('<script data-page="app" type="application/json">', false)
        ->assertSee('<div id="app"></div>', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->has('navigation.primary', 7)
            ->where('navigation.primary.2.id', 'services')
            ->where('navigation.primary.2.type', 'menu')
            ->has('navigation.primary.2.links', 2)
            ->has('navigation.mobile', 10)
            ->has('navigation.footer.footer_legal')
            ->where('site.locale.current', 'en')
            ->where('site.locale.alternate', 'am')
            ->where('ui.skip', 'Skip to content')
            ->where('ui.acceptOptional', 'Accept optional')
            ->where('ui.rejectOptional', 'Reject optional')
            ->where('ui.manageChoices', 'Manage choices'));
});

it('localizes the React shell on the server so the client never loads the dictionary', function (): void {
    $this->get('/am')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->where('site.locale.current', 'am')
            ->where('ui.skip', __('Skip to content', [], 'am'))
            ->missing('translations'));
});

it('keeps the strict CSP and lets only nonce-bearing inline scripts run', function (): void {
    $policy = $this->get('/en')->assertOk()->headers->get('Content-Security-Policy');

    expect($policy)
        ->toContain("default-src 'self'")
        ->toMatch("/script-src 'self' 'nonce-[A-Za-z0-9]+'/")
        ->not->toContain("'unsafe-eval'")
        ->not->toContain("script-src 'self' 'unsafe-inline'");
});

it('builds the React shell with the accessibility contract of the Blade shell', function (): void {
    $layout = File::get(resource_path('js/Layouts/PublicLayout.jsx'));
    $header = File::get(resource_path('js/Components/Public/SiteHeader.jsx'));
    $consent = File::get(resource_path('js/Components/Public/ConsentBanner.jsx'));
    $slider = File::get(resource_path('js/Components/Public/HeroSlider.jsx'));
    $entry = File::get(resource_path('js/inertia.jsx'));

    expect($layout)
        ->toContain('href="#main-content"')
        ->toContain('id="main-content"')
        ->toContain('aria-live="polite"')
        ->and($header)
        ->toContain('aria-controls={`mega-${item.id}`}')
        ->toContain('aria-expanded={activeMenu === item.id}')
        ->toContain('aria-modal="true"')
        ->toContain('trapFocus')
        ->toContain("event.key !== 'Escape'")
        ->toContain('hrefLang={site.locale.alternate}')
        ->and($consent)
        ->toContain('aria-modal="true"')
        ->toContain("const STORAGE_KEY = 'impact.consent'")
        ->toContain('Equal visual weight')
        ->and($slider)
        ->toContain('useReducedMotion')
        ->toContain('pauseSlides')
        ->and($entry)
        ->toContain('reducedMotion="user"');
});
