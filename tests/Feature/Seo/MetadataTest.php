<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\SeoMetadata;
use App\Models\Setting;
use App\Support\Settings\EffectiveSettings;

beforeEach(function (): void {
    asProduction();
});

it('renders a unique title, description, canonical, robots and social metadata in the server HTML', function (): void {
    publishService('digital-transformation');

    $html = $this->get('https://localhost/services/digital-transformation')->assertOk()->getContent();

    expect($html)
        ->toContain('<title data-inertia="title">Digital transformation consulting | Impact Consulting</title>')
        ->toContain('<meta data-inertia="description" name="description" content="Practical Digital transformation advice that connects strategy, operating models and delivery for public institutions.">')
        ->toContain('<link data-inertia="canonical" rel="canonical" href="https://localhost/services/digital-transformation">')
        ->toContain('<meta data-inertia="robots" name="robots" content="index, follow">')
        ->toContain('<meta data-inertia="og:title" property="og:title" content="Digital transformation consulting | Impact Consulting">')
        ->toContain('<meta data-inertia="og:url" property="og:url" content="https://localhost/services/digital-transformation">')
        ->toContain('<meta data-inertia="og:type" property="og:type" content="website">')
        ->toContain('<meta data-inertia="og:locale" property="og:locale" content="en_US">')
        ->toContain('property="og:image" content="https://localhost/images/optimized/ethiopia-highlands-social.jpg"')
        ->toContain('property="og:image:alt"')
        ->toContain('name="twitter:card" content="summary_large_image"')
        ->not->toContain('content=""')
        ->not->toContain('name="keywords"');
});

it('uses the configured homepage title verbatim and adds the brand suffix exactly once elsewhere', function (): void {
    $this->get('https://localhost/')->assertOk()
        ->assertSee('<title data-inertia="title">Impact Consulting | Strategic Advisory &amp; Professional Consulting</title>', false);

    // "Impact Consulting" already ends the title: no repeated suffix.
    $this->get('https://localhost/about')->assertOk()
        ->assertSee('<title data-inertia="title">About Impact Consulting</title>', false);

    // Case-sensitive brand match: a lowercase phrase still gets the suffix.
    publishIndustry('social-impact', 'Social impact');
    $this->get('https://localhost/industries/social-impact')->assertOk()
        ->assertSee('<title data-inertia="title">Social impact consulting | Impact Consulting</title>', false);
});

it('applies the fallback order: SEO override, then content, then the global default', function (): void {
    $service = publishService('strategy', 'Strategy', ['summary' => null, 'problem_statement' => null, 'approach' => null]);

    $this->get('https://localhost/services/strategy')
        ->assertSee('name="description" content="Evidence-led consulting for lasting impact."', false);

    SeoMetadata::query()->create([
        'subject_type' => 'service', 'subject_key' => $service->service_id,
        'meta_title' => 'Strategy advisory for public institutions',
        'meta_description' => 'Independent strategy advice that turns public-sector ambition into a focused, fundable delivery plan.',
        'include_in_sitemap' => true,
    ]);

    $this->get('https://localhost/services/strategy')
        ->assertSee('<title data-inertia="title">Strategy advisory for public institutions | Impact Consulting</title>', false)
        ->assertSee('content="Independent strategy advice that turns public-sector ambition into a focused, fundable delivery plan."', false);
});

it('keeps long titles readable by dropping the suffix rather than truncating meaning', function (): void {
    // 51 characters, 71 with the suffix: the descriptive part is kept whole.
    publishCaseStudy('national-delivery', 'Building a delivery system for a national programme');

    $this->get('https://localhost/case-studies/national-delivery')
        ->assertSee('<title data-inertia="title">Building a delivery system for a national programme</title>', false);
});

it('emits search engine verification tags only when configured, on the homepage', function (): void {
    $this->get('https://localhost/')->assertDontSee('google-site-verification', false);

    Setting::query()->create(['key' => 'seo.google_site_verification', 'scope' => 'global', 'type' => 'string', 'value' => 'abcdefghijklmnopqrstuvwxyz012345']);
    EffectiveSettings::flushCaches();
    app()->forgetScopedInstances();

    $this->get('https://localhost/')->assertSee('<meta data-inertia="google-site-verification" name="google-site-verification" content="abcdefghijklmnopqrstuvwxyz012345">', false);
    $this->get('https://localhost/about')->assertDontSee('google-site-verification', false);
});

it('keeps metadata for client-side visits in the Inertia payload', function (): void {
    publishService('digital-transformation');

    $this->get('https://localhost/services/digital-transformation', ['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()) ?? ''])
        ->assertOk()
        ->assertJsonPath('props.seo.title', 'Digital transformation consulting | Impact Consulting')
        ->assertJsonPath('props.seo.canonical', 'https://localhost/services/digital-transformation')
        ->assertJsonPath('props.seo.robots', 'index, follow');
});
