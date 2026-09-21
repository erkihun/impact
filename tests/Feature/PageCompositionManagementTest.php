<?php

declare(strict_types=1);

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

it('seeds complete bilingual page coverage and passes the strict verifier', function (): void {
    Cache::flush();
    User::factory()->create();
    $this->seed([PageCompositionSeeder::class, NavigationConfigurationSeeder::class]);

    expect(Artisan::call('public-content:verify', ['--strict' => true]))->toBe(0)
        ->and(PageComposition::query()->where('state', PageCompositionState::Published)->count())
        ->toBe(52);
});

it('denies page composition administration without page permissions', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.page-compositions.index'))
        ->assertForbidden();
});

it('renders managed public headers in both locales and the three-panel editor workspace', function (): void {
    Cache::flush();
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $this->seed([SettingSeeder::class, PageCompositionSeeder::class, NavigationConfigurationSeeder::class]);

    $this->get('/en')
        ->assertOk()
        ->assertSee('Evidence for the decisions that shape institutions.')
        ->assertSee('x-data="homepageHeroSlider"', escape: false)
        ->assertSee('data-slide-count="3"', escape: false);
    $this->get('/am')
        ->assertOk()
        ->assertSee('ለተቋማት የወደፊት አቅጣጫ በሚወስኑ ውሳኔዎች ላይ የተመሠረተ ማስረጃ።');

    $composition = PageComposition::query()
        ->where('page_key', 'home')
        ->where('locale', 'en')
        ->sole();
    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->get(route('admin.page-compositions.edit', $composition))
        ->assertOk()
        ->assertSee('የክፍል ቤተ መዘክር')
        ->assertSee('የገጽ አጠቃላይ ቅርጽ')
        ->assertSee('የገጽ መቆጣጠሪያዎች')
        ->assertSee('ሊርትዑ የሚችሉትን ረቂቅ ይፍጠሩ');

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
