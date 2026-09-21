<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('renders the impact intelligence homepage with its signature visual language', function (): void {
    $this->get('/en')
        ->assertOk()
        ->assertSee('Impact Intelligence')
        ->assertSee('Evidence for the decisions that shape institutions.')
        ->assertSee('impact-intelligence-hero-v1.webp')
        ->assertSee('insight-marker', false)
        ->assertSee('home-signature-hero-section', false)
        ->assertSee('home-signature-image', false)
        ->assertSee('x-data="homepageHeroSlider"', false)
        ->assertSee('data-slide-count="3"', false)
        ->assertSee('home-hero-slider-stage', false)
        ->assertSee('x-ref="stage"', false)
        ->assertSee('data-home-hero-slide', false)
        ->assertSee('x-transition:enter="home-hero-transition-enter"', false)
        ->assertSee('home-hero-slider-controls', false)
        ->assertDontSee('home-modern-hero-section', false)
        ->assertSee('Engagement pathway');
});

it('renders trustworthy two-column consultation and proposal intake', function (string $url, string $contextHeading): void {
    $this->get($url)
        ->assertOk()
        ->assertSee('lg:grid-cols-[minmax(0,1fr)_20rem]', false)
        ->assertSee('form-context-panel', false)
        ->assertSee($contextHeading)
        ->assertSee('data-prevent-duplicate', false);
})->with([
    ['/en/consultation', 'The clearest requests start with the decision.'],
    ['/en/request-for-proposal', 'A useful brief makes the evaluation criteria visible.'],
]);

it('defines reusable impact intelligence components and restrained motion', function (): void {
    foreach ([
        'impact-line.blade.php',
        'impact-index.blade.php',
        'kpi-card.blade.php',
        'insight-marker.blade.php',
        'editorial-number.blade.php',
    ] as $component) {
        expect(File::exists(resource_path("views/components/ui/{$component}")))->toBeTrue();
    }

    $css = File::get(resource_path('css/app.css'));
    $homepage = File::get(resource_path('views/public/home.blade.php'));

    expect($css)
        ->toContain('.impact-line')
        ->toContain('.impact-index')
        ->toContain('.public-kpi-card')
        ->toContain('.advisory-kpi-grid')
        ->toContain('.home-signature-hero-section')
        ->toContain('.home-hero-slider-stage')
        ->toContain('.home-hero-transition-enter')
        ->toContain('.home-hero-transition-leave')
        ->toContain('.home-hero-slider-controls')
        ->toContain('.home-proof-ledger')
        ->toContain('.home-capability-register')
        ->toContain('.home-record')
        ->toContain('.home-sector-matrix')
        ->toContain('.home-perspectives-grid')
        ->toContain('.home-next-move-section')
        ->toContain('.insight-marker')
        ->toContain('.editorial-number')
        ->toContain('@media (prefers-reduced-motion: reduce)')
        ->and($homepage)
        ->toContain("__('Current publication record')")
        ->toContain('home-proof-ledger')
        ->toContain("__('Advisory architecture')")
        ->toContain('home-capability-register')
        ->not->toContain('home-modern-');
});

it('ships the optimized project-bound hero asset', function (): void {
    $asset = public_path('images/impact-intelligence-hero-v1.webp');

    expect(File::exists($asset))->toBeTrue()
        ->and(File::size($asset))->toBeLessThan(250_000);
});

it('uses a compact public editorial typography scale', function (): void {
    $css = File::get(resource_path('css/app.css'));
    $homepage = File::get(resource_path('views/public/home.blade.php'));

    expect($css)
        ->toContain('text-3xl font-black leading-[1.1]')
        ->toContain('text-2xl font-extrabold leading-[1.15]')
        ->toContain('font-mono text-3xl font-black')
        ->not->toContain('lg:text-[4.75rem]')
        ->not->toContain('lg:text-6xl')
        ->and($homepage)
        ->not->toContain('sm:text-5xl');
});
