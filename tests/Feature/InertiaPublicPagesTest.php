<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->withHeader('X-Inertia-Version', app(HandleInertiaRequests::class)->version(request()) ?? '');
});

it('serves public routes through Inertia without a language prefix and with server-resolved metadata', function (string $path, string $component): void {
    $this->get($path, ['X-Inertia' => 'true'])
        ->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', "Public/{$component}")
        ->assertJsonPath('props.seo.canonical', url($path))
        ->assertJsonMissingPath('props.site.locale');
})->with([
    ['/about', 'About'],
    ['/services', 'Collection'],
    ['/industries', 'Collection'],
    ['/experts', 'Collection'],
    ['/case-studies', 'Collection'],
    ['/insights', 'Collection'],
    ['/events', 'Collection'],
    ['/careers', 'Collection'],
    ['/consultation', 'Engagement'],
    ['/request-for-proposal', 'Engagement'],
    ['/contact', 'Engagement'],
    ['/privacy', 'Legal'],
    ['/terms', 'Legal'],
    ['/cookies', 'Legal'],
    ['/accessibility', 'Legal'],
    ['/search', 'Search'],
]);

it('supplies fresh metadata for client visits and preserves search indexing rules', function (): void {
    $this->get('/about')
        ->assertOk()
        ->assertSee('data-inertia="canonical"', false)
        ->assertSee('data-inertia="description"', false)
        ->assertSee('data-inertia="robots"', false);

    $this->get('/search?q=strategy', ['X-Inertia' => 'true'])
        ->assertOk()
        ->assertJsonPath('props.seo.intendedRobots', 'noindex, follow')
        ->assertJsonPath('props.seo.canonical', url('/search'))
        ->assertJsonPath('props.seo.sitemapEligible', false);
});

it('retains consultation context in the React form payload', function (): void {
    $this->get('/consultation?service_id=7&industry_id=9')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Engagement')
            ->where('form.hidden.service_id', '7')
            ->where('form.hidden.industry_id', '9')
            ->where('form.hidden.type', 'consultation')
            ->where('form.steps.0.notice', __('This request includes context selected from the page you were viewing. You can still describe a different need below.'))
            // Context parameters never create a second indexable URL.
            ->where('seo.canonical', url('/consultation'))
            ->where('seo.intendedRobots', 'noindex, follow'));
});
