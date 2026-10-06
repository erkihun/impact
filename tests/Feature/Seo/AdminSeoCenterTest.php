<?php

declare(strict_types=1);

use App\Models\ContentRelation;
use App\Models\Redirect;
use App\Models\Role;
use App\Models\SeoMetadata;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::query()->where('code', 'administrator')->sole());
});

it('shows every SEO centre section to SEO managers and nothing to other staff', function (string $section): void {
    publishService('digital-transformation');

    $this->actingAs($this->admin)->withSession(privilegedSession($this->admin))
        ->get("/admin/seo?section={$section}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Seo/Index')->where('section', $section)
            ->missing('sections.translations'));
})->with(['overview', 'pages', 'metadata', 'sitemap', 'redirects', 'structured-data', 'links', 'indexing', 'quality', 'social', 'settings', 'audit']);

it('denies the SEO centre without the seo.manage permission', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());

    $this->actingAs($editor)->withSession(privilegedSession($editor))->get('/admin/seo')->assertForbidden();
});

it('runs an audit from the admin without disturbing the administrator session', function (): void {
    publishService('digital-transformation');

    $this->actingAs($this->admin)->withSession(privilegedSession($this->admin))
        ->from('/admin/seo?section=audit')
        ->post('/admin/seo/audit')
        ->assertRedirect('/admin/seo?section=audit');

    $this->get('/admin/seo?section=audit')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('data.runs', 1)->where('workspace.user.id', $this->admin->id));
    $this->assertDatabaseHas('audit_events', ['action' => 'seo.audit.run']);
});

it('edits page SEO with previews and validation, and a published slug change leaves a 301', function (): void {
    $service = publishService('digital-transformation');
    $expert = publishExpert('jane-doe');
    $url = "/admin/seo/pages/service/{$service->service_id}";

    $this->actingAs($this->admin)->withSession(privilegedSession($this->admin))
        ->get($url)->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Seo/Edit')
            ->where('subject.path', '/services/digital-transformation')
            ->where('preview.title', 'Digital transformation consulting | Impact Consulting')
            ->where('validation.status', 'warnings')
            ->has('relatedOptions')
            ->where('workspace.user.id', $this->admin->id));

    $this->put($url, [
        'meta_title' => 'Digital government consulting',
        'meta_description' => 'Practical advice for ministries and agencies modernizing services, data and delivery with measurable results.',
        'slug' => 'digital-government',
        'robots' => '',
        'canonical_path' => '',
        'include_in_sitemap' => true,
        'geographic_relevance' => ['Ethiopia'],
        'related' => ["expert:{$expert->expert_id}"],
    ])->assertRedirect($url)->assertSessionHasNoErrors();

    expect(SeoMetadata::query()->where('subject_key', $service->service_id)->sole()->meta_title)->toBe('Digital government consulting')
        ->and(ContentRelation::query()->where('source_id', $service->service_id)->value('target_id'))->toBe($expert->expert_id)
        ->and(Redirect::query()->where('source_path', '/services/digital-transformation')->value('destination_url'))->toBe('/services/digital-government');
    $this->get('/services/digital-government')->assertSee('<title data-inertia="title">Digital government consulting | Impact Consulting</title>', false);
    expect(structuredData($this->get('/services/digital-government'))['Service']['areaServed'])->toBe(['Ethiopia']);
    $this->assertDatabaseHas('audit_events', ['action' => 'seo.metadata.updated']);
});

it('refuses unsafe canonical overrides and links to unpublished content', function (): void {
    $service = publishService('digital-transformation');
    $draft = publishInsight('draft', 'Draft');
    $draft->update(['workflow_state' => 'draft']);

    $this->actingAs($this->admin)->withSession(privilegedSession($this->admin))
        ->put("/admin/seo/pages/service/{$service->service_id}", [
            'slug' => 'digital-transformation', 'include_in_sitemap' => true,
            'canonical_path' => 'https://competitor.example.com/page',
        ])->assertSessionHasErrors('canonical_path');

    $this->put("/admin/seo/pages/service/{$service->service_id}", [
        'slug' => 'digital-transformation', 'include_in_sitemap' => true,
        'related' => ["insight:{$draft->insight_id}"],
    ])->assertSessionHasErrors('related');
});

it('manages redirects with validation, chain flattening and disable-not-delete history', function (): void {
    publishService('digital-transformation');
    $session = fn () => $this->actingAs($this->admin)->withSession(privilegedSession($this->admin));

    $session()->post('/admin/seo/redirects', ['source_path' => '/old-services', 'destination_url' => '/services/digital-transformation', 'status_code' => 301, 'reason' => 'Renamed'])
        ->assertSessionHasNoErrors();
    $session()->post('/admin/seo/redirects', ['source_path' => '/services/digital-transformation', 'destination_url' => '/services', 'status_code' => 301, 'reason' => 'Hide'])
        ->assertSessionHasErrors('source_path');
    $session()->post('/admin/seo/redirects', ['source_path' => '/elsewhere', 'destination_url' => 'https://example.org', 'status_code' => 301, 'reason' => 'External'])
        ->assertSessionHasErrors('destination_url');

    $redirect = Redirect::query()->where('source_path', '/old-services')->sole();
    $session()->delete("/admin/seo/redirects/{$redirect->id}")->assertSessionHasNoErrors();
    expect($redirect->refresh()->enabled)->toBeFalse();
    $this->get('/old-services')->assertNotFound();
});
