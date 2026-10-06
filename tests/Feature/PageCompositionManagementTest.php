<?php

declare(strict_types=1);

use App\Actions\PageComposition\CreatePageCompositionDraftAction;
use App\Enums\PageCompositionState;
use App\Enums\PageTemplateType;
use App\Models\PageComposition;
use App\Models\PageTemplate;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\NavigationConfigurationSeeder;
use Database\Seeders\PageCompositionSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

function draftComposition(User $actor): PageComposition
{
    $template = PageTemplate::query()->create([
        'key' => 'test-institutional',
        'type' => PageTemplateType::Institutional,
        'name' => 'Institutional',
        'definition' => ['approved' => true],
        'active' => true,
    ]);

    return PageComposition::query()->create([
        'template_id' => $template->getKey(),
        'page_key' => 'test.page',
        'locale' => 'en',
        'template_type' => PageTemplateType::Institutional,
        'state' => PageCompositionState::Draft,
        'version_no' => 1,
        'lock_version' => 1,
        'content_hash' => hash('sha256', 'test.page|en'),
        'created_by' => $actor->getKey(),
    ]);
}

it('adds and updates immutable section versions with optimistic locking and audit evidence', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $composition = draftComposition($editor);

    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->post(route('admin.page-compositions.sections.store', $composition), [
            'type' => 'page_header',
            'variant' => 'standard',
            'editor_label' => 'Page introduction',
            'content' => ['heading' => 'Governed page', 'summary' => 'Managed summary'],
            'presentation' => [
                'surface_tone' => 'default',
                'container_width' => 'standard',
                'spacing_top' => 'standard',
            ],
            'enabled' => true,
            'visibility_rule' => 'always',
            'lock_version' => 1,
        ])
        ->assertRedirect(route('admin.page-compositions.edit', $composition));

    $composition->refresh()->load('sections.currentVersion');
    $section = $composition->sections->sole();
    expect($composition->lock_version)->toBe(2)
        ->and($section->versions()->count())->toBe(1);

    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->patchJson(route('admin.page-compositions.sections.update', [$composition, $section]), [
            'type' => 'page_header',
            'variant' => 'standard',
            'editor_label' => 'Page introduction',
            'content' => ['heading' => 'Governed page revised', 'summary' => 'Managed summary'],
            'presentation' => [
                'surface_tone' => 'default',
                'container_width' => 'standard',
                'spacing_top' => 'standard',
            ],
            'enabled' => true,
            'visibility_rule' => 'always',
            'lock_version' => 2,
        ])
        ->assertRedirect();

    expect($section->versions()->count())->toBe(2)
        ->and($composition->refresh()->lock_version)->toBe(3);
    $this->assertDatabaseHas('audit_events', ['action' => 'page_section.updated']);

    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->patchJson(route('admin.page-compositions.sections.update', [$composition, $section]), [
            'type' => 'page_header',
            'variant' => 'standard',
            'editor_label' => 'Stale edit',
            'content' => ['heading' => 'Stale heading'],
            'presentation' => [],
            'enabled' => true,
            'visibility_rule' => 'always',
            'lock_version' => 2,
        ])
        ->assertConflict()
        ->assertJsonPath('code', 'CONTENT_VERSION_CONFLICT');
});

it('seeds complete English page coverage and passes the strict verifier', function (): void {
    Cache::flush();
    User::factory()->create();
    $this->seed([PageCompositionSeeder::class, NavigationConfigurationSeeder::class]);

    expect(Artisan::call('public-content:verify', ['--strict' => true]))->toBe(0)
        ->and(PageComposition::query()->where('state', PageCompositionState::Published)->count())
        ->toBe(26);
});

it('denies page composition administration without page permissions', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.page-compositions.index'))
        ->assertForbidden();
});

it('lists each English page once with its newest version and paginates pages', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $source = draftComposition($editor);
    $source->forceFill(['page_key' => 'page.00'])->save();
    for ($index = 0; $index < 31; $index++) {
        foreach ([['en', 1], ['en', 2], ['am', 1]] as [$locale, $version]) {
            if ($index === 0 && $locale === 'en' && $version === 1) {
                continue;
            }
            $source->replicate()->forceFill([
                'page_key' => sprintf('page.%02d', $index),
                'locale' => $locale,
                'version_no' => $version,
            ])->save();
        }
    }
    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->get(route('admin.page-compositions.index'))
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->has('compositions.data', 30)
        ->where('compositions.total', 31)
        ->where('compositions.data.0.page_key', 'page.00')
        ->has('compositions.data.0.translations', 1)
        ->where('compositions.data.0.translations.0.locale', 'en')
        ->where('compositions.data.0.translations.0.version_no', 2));
    $this->get(route('admin.page-compositions.index', ['page' => 2]))
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->has('compositions.data', 1)
        ->where('compositions.data.0.page_key', 'page.30')
        ->has('compositions.data.0.translations', 1));
});

it('filters the latest page status and searches across all pages', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $published = draftComposition($editor);
    $published->forceFill(['page_key' => 'home', 'state' => PageCompositionState::Published])->save();
    $published->replicate()->forceFill(['version_no' => 2, 'state' => PageCompositionState::Draft])->save();
    $published->replicate()->forceFill(['page_key' => 'about'])->save();
    $this->actingAs($editor)->withSession(privilegedSession($editor));
    $this->get('/admin/page-compositions?state=published')->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('compositions.total', 1)
            ->where('compositions.data.0.page_key', 'about')
            ->where('summary.total', 2)->where('summary.published', 1)->where('summary.in_progress', 1));
    $this->get('/admin/page-compositions?q=Homepage&state=draft')->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('compositions.total', 1)->where('compositions.data.0.page_key', 'home')
            ->where('compositions.data.0.translations.0.version_no', 2)
            ->where('filters.q', 'Homepage'));
    $this->get('/admin/page-compositions?q=missing')->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('compositions.total', 0));
    $this->get('/admin/page-compositions?state=unknown')->assertSessionHasErrors('state');
});

it('exposes English version history without mixing other pages into the editor', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $english = draftComposition($editor);
    $amharic = $english->replicate()->forceFill(['locale' => 'am'])->save();
    $english->replicate()->forceFill(['version_no' => 2])->save();
    $english->replicate()->forceFill(['page_key' => 'other.page'])->save();
    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->get(route('admin.page-compositions.edit', $english))
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('composition.id', $english->id)
        ->where('composition.locale', 'en')
        ->has('pageVersions', 2)
        ->where('pageVersions.0.locale', 'en')
        ->where('pageVersions.0.version_no', 2)
        ->where('pageVersions.1.id', $english->id));
});

it('renders managed English public headers and the three-panel editor workspace', function (): void {
    Cache::flush();
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $this->seed([SettingSeeder::class, PageCompositionSeeder::class, NavigationConfigurationSeeder::class]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Evidence for the decisions that shape institutions.')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Home')
            ->has('heroSlider.slides', 3));
    $this->get('/am')->assertRedirect('/');

    $composition = PageComposition::query()
        ->where('page_key', 'home')
        ->where('locale', 'en')
        ->sole();
    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->get(route('admin.page-compositions.edit', $composition))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/PageCompositions/Edit'));

    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->post(route('admin.page-compositions.drafts.store', $composition))
        ->assertRedirect();
    $draft = PageComposition::query()
        ->where('based_on_id', $composition->getKey())
        ->where('state', PageCompositionState::Draft)
        ->with('sections.currentVersion')
        ->sole();
    expect($draft->version_no)->toBe(2)
        ->and($draft->sections)->toHaveCount(1)
        ->and($draft->sections->sole()->currentVersion->content['heading'])
        ->toBe('Evidence for the decisions that shape institutions.');

    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->post(route('admin.page-compositions.transitions.store', $draft), ['to' => 'in_review'])
        ->assertRedirect();
    $reviewer = User::factory()->create();
    $reviewer->roles()->attach(Role::query()->where('code', 'reviewer')->sole());
    $this->actingAs($reviewer)->withSession(privilegedSession($reviewer))
        ->post(route('admin.page-compositions.transitions.store', $draft), [
            'to' => 'approved',
            'comment' => 'Independent review complete.',
        ])
        ->assertRedirect();
    $publisher = User::factory()->create();
    $publisher->roles()->attach(Role::query()->where('code', 'publisher')->sole());
    $this->actingAs($publisher)->withSession(privilegedSession($publisher))
        ->post(route('admin.page-compositions.transitions.store', $draft), [
            'to' => 'published',
            'comment' => 'Approved for release.',
        ])
        ->assertRedirect();

    expect($draft->refresh()->state)->toBe(PageCompositionState::Published)
        ->and($composition->refresh()->state)->toBe(PageCompositionState::Archived);
    $this->assertDatabaseHas('page_composition_workflow_events', ['to_state' => 'published']);
});

function quickPublishDraft(User $actor): PageComposition
{
    $thisTest = test();
    $thisTest->seed([PageCompositionSeeder::class]);
    $source = PageComposition::query()->where('page_key', 'home')->sole();

    return app(CreatePageCompositionDraftAction::class)
        ->execute($actor, $source, (string) Str::uuid());
}

it('publishes a saved draft in one request with all workflow audit steps', function (): void {
    $actor = User::factory()->create();
    $actor->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $draft = quickPublishDraft($actor);

    $this->actingAs($actor)->withSession(privilegedSession($actor))
        ->post(route('admin.page-compositions.publish', $draft), ['lock_version' => $draft->lock_version])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.page-compositions.edit', $draft));

    expect($draft->refresh()->state)->toBe(PageCompositionState::Published);
    foreach (['in_review', 'approved', 'published'] as $state) {
        $this->assertDatabaseHas('page_composition_workflow_events', [
            'page_composition_id' => $draft->id, 'to_state' => $state,
        ]);
    }
    expect(PageComposition::query()->where('page_key', 'home')->where('state', PageCompositionState::Published)->count())->toBe(1);
});

it('does not let a publisher skip permissions required for draft approval', function (): void {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $draft = quickPublishDraft($admin);
    $publisher = User::factory()->create();
    $publisher->roles()->attach(Role::query()->where('code', 'publisher')->sole());

    $this->actingAs($publisher)->withSession(privilegedSession($publisher))
        ->post(route('admin.page-compositions.publish', $draft), ['lock_version' => $draft->lock_version])
        ->assertForbidden();
    expect($draft->refresh()->state)->toBe(PageCompositionState::Draft);
    $this->assertDatabaseMissing('page_composition_workflow_events', ['page_composition_id' => $draft->id]);
});

it('keeps invalid pages as drafts when quick publication fails validation', function (): void {
    $actor = User::factory()->create();
    $actor->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $draft = draftComposition($actor);
    $this->actingAs($actor)->withSession(privilegedSession($actor))
        ->post(route('admin.page-compositions.publish', $draft), ['lock_version' => $draft->lock_version])
        ->assertSessionHasErrors('composition');
    expect($draft->refresh()->state)->toBe(PageCompositionState::Draft);
});

it('rejects quick publication when saved sections changed after opening the page', function (): void {
    $actor = User::factory()->create();
    $actor->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $draft = quickPublishDraft($actor);
    $this->actingAs($actor)->withSession(privilegedSession($actor))
        ->postJson(route('admin.page-compositions.publish', $draft), ['lock_version' => $draft->lock_version + 1])
        ->assertConflict();
    expect($draft->refresh()->state)->toBe(PageCompositionState::Draft);
});
