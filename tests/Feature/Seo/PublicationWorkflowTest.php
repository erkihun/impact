<?php

declare(strict_types=1);

use App\Contracts\CdnPurger;
use App\Enums\Seo\PublicResourceType;
use App\Models\ContentItem;
use App\Models\ContentRelation;
use App\Models\ContentVersion;
use App\Models\Role;
use App\Models\SearchDocument;
use App\Models\User;
use App\Services\Seo\InternalLinkService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('updates internal search, the sitemap and the CDN when content is published, moved and archived', function (): void {
    $purged = [];
    app()->instance(CdnPurger::class, new class($purged) implements CdnPurger
    {
        public function __construct(private array &$purged) {}

        public function purge(array $urls): void
        {
            $this->purged = [...$this->purged, ...$urls];
        }
    });

    $service = publishService('digital-transformation');
    expect(SearchDocument::query()->where('url', '/services/digital-transformation')->exists())->toBeTrue()
        ->and($purged)->toContain('http://localhost/services/digital-transformation');

    $service->update(['slug' => 'digital-government']);
    expect(SearchDocument::query()->where('url', '/services/digital-government')->exists())->toBeTrue()
        ->and(SearchDocument::query()->where('url', '/services/digital-transformation')->exists())->toBeFalse()
        ->and($purged)->toContain('http://localhost/services/digital-government');

    $service->service->update(['status' => 'archived']);
    expect(SearchDocument::query()->where('searchable_type', 'service')->exists())->toBeFalse();
    $this->get('/sitemaps/services.xml')->assertNotFound();
});

it('blocks publishing CMS content that has SEO blocking issues', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    $publisher = User::factory()->create();
    $publisher->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $content = ContentItem::query()->create(['type' => 'page', 'owner_id' => $publisher->id, 'status' => 'approved']);
    $version = ContentVersion::query()->create([
        'content_item_id' => $content->id, 'locale' => 'en', 'version_no' => 1, 'slug' => 'Bad_Slug',
        'title' => 'A page with an invalid slug', 'body' => ['content' => 'Body'], 'workflow_state' => 'approved',
        'created_by' => $publisher->id, 'content_hash' => str_repeat('c', 64),
    ]);
    $content->update(['current_version_id' => $version->id]);

    $this->actingAs($publisher)->withSession(privilegedSession($publisher))
        ->post("/admin/content/{$content->id}/transitions", ['content_version_id' => $version->id, 'to' => 'published', 'note' => 'Go'])
        ->assertSessionHasErrors('to');
    expect($content->refresh()->status->value)->toBe('approved');
});

it('normalizes slugs to lowercase hyphenated words', function (string $input, string $expected): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());

    $this->actingAs($editor)->withSession(privilegedSession($editor))->post('/admin/content', [
        'type' => 'page', 'locale' => 'en', 'slug' => $input, 'title' => 'Title', 'summary' => 'Summary', 'body' => 'Body',
    ])->assertSessionHasNoErrors();
    expect(ContentVersion::query()->sole()->slug)->toBe($expected);
})->with([['Upper Case', 'upper-case'], ['under_score', 'under-score'], ['double--hyphen', 'double-hyphen'], [' Ünïcode Strategy! ', 'unicode-strategy']]);

it('rejects reserved slugs', function (string $slug): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());

    $this->actingAs($editor)->withSession(privilegedSession($editor))->post('/admin/content', [
        'type' => 'page', 'locale' => 'en', 'slug' => $slug, 'title' => 'Title', 'summary' => 'Summary', 'body' => 'Body',
    ])->assertSessionHasErrors('slug');
})->with(['admin', 'en', 'am', 'search', 'sitemap']);

it('links related published content contextually and never links unpublished content', function (): void {
    $service = publishService('digital-transformation');
    $industry = publishIndustry('public-sector');
    $expert = publishExpert('jane-doe');
    $draft = publishInsight('draft-insight', 'Draft insight');
    $draft->update(['workflow_state' => 'draft']);
    DB::table('service_industry')->insert(['service_id' => $service->service_id, 'industry_id' => $industry->industry_id, 'sort_order' => 0, 'featured' => false]);
    DB::table('expert_service')->insert(['expert_id' => $expert->expert_id, 'service_id' => $service->service_id, 'sort_order' => 0, 'featured' => false]);
    ContentRelation::query()->create(['source_type' => 'insight', 'source_id' => $draft->insight_id, 'target_type' => 'service', 'target_id' => $service->service_id]);

    $this->get('/services/digital-transformation')->assertInertia(fn (Assert $page) => $page
        ->where('related.0.heading', 'Industries we support with this service')
        ->where('related.0.items.0.href', '/industries/public-sector')
        ->where('related.1.items.0.name', 'Jane Doe')
        ->has('related', 2));

    // The industry links back without any curated relation.
    $this->get('/industries/public-sector')->assertInertia(fn (Assert $page) => $page
        ->where('related.0.items.0.href', '/services/digital-transformation'));

    expect(app(InternalLinkService::class)->contextualLinkCount(PublicResourceType::Service, $service->service_id))->toBe(2);
});
