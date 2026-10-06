<?php

declare(strict_types=1);

use App\Models\ContentItem;
use App\Models\ContentVersion;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\URL;

it('makes published pages indexable and internal search noindex, follow in production', function (): void {
    asProduction();
    publishService('digital-transformation');

    $this->get('https://localhost/services/digital-transformation')
        ->assertSee('name="robots" content="index, follow"', false)
        ->assertHeaderMissing('X-Robots-Tag');
    $this->get('https://localhost/search?q=strategy')
        ->assertSee('name="robots" content="noindex, follow"', false)
        ->assertSee('rel="canonical" href="https://localhost/search"', false);
});

it('never serves drafts, unpublished or archived resources', function (): void {
    publishService('draft-service', 'Draft', ['workflow_state' => 'draft']);
    $unpublished = publishService('unpublished-service', 'Unpublished');
    $unpublished->service->update(['status' => 'unpublished']);
    $archived = publishService('archived-service', 'Archived');
    $archived->service->update(['status' => 'archived']);

    $this->get('/services/draft-service')->assertNotFound()->assertSee('name="robots" content="noindex, nofollow"', false);
    $this->get('/services/unpublished-service')->assertNotFound();
    $this->get('/services/archived-service')->assertStatus(410);
});

it('noindexes everything outside production, including the response header', function (): void {
    publishService('digital-transformation');

    $this->get('/services/digital-transformation')
        ->assertSee('name="robots" content="noindex, nofollow"', false)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('keeps admin, sign-in, previews and private downloads out of the index in production', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    asProduction();
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());

    $this->get('https://localhost/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('name="robots" content="noindex, nofollow"', false);

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->get('https://localhost/admin')->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertDontSee('rel="canonical"', false);

    $content = ContentItem::query()->create(['type' => 'page', 'owner_id' => $admin->id, 'status' => 'draft']);
    $version = ContentVersion::query()->create([
        'content_item_id' => $content->id, 'locale' => 'en', 'version_no' => 1, 'slug' => 'draft-page',
        'title' => 'Draft page', 'body' => ['content' => 'Draft'], 'workflow_state' => 'draft',
        'created_by' => $admin->id, 'content_hash' => str_repeat('b', 64),
    ]);
    $content->update(['current_version_id' => $version->id]);
    $preview = URL::temporarySignedRoute('admin.content.preview', now()->addMinutes(5), ['content' => $content, 'version' => $version]);

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->get(str_replace('http://', 'https://', $preview))->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertSee('name="robots" content="noindex', false);
});

it('never lists private or confidential content in the sitemap or structured data', function (): void {
    publishService('digital-transformation');
    $sitemap = collect(['pages', 'services'])
        ->map(fn (string $segment): string => (string) $this->get("/sitemaps/{$segment}.xml")->getContent())
        ->implode('');

    expect($sitemap)->not->toContain('/admin')->not->toContain('/login')->not->toContain('/search')
        ->not->toContain('restricted-media')->not->toContain('/storage/');
    expect($this->get('/services/digital-transformation')->getContent())->not->toContain('consultation-requests')
        ->not->toContain('engagement_submissions');
});
