<?php

declare(strict_types=1);

use App\Models\Locale;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings\EffectiveSettings;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('serves English despite retired session and stored language preferences', function (): void {
    foreach (['localization.default_locale', 'localization.fallback_locale', 'localization.enabled_locales'] as $key) {
        Setting::query()->where('key', $key)->update(['value' => json_encode('am')]);
    }
    EffectiveSettings::flushCaches();
    $this->withSession(['locale' => 'am'])->get('/')
        ->assertOk()
        ->assertSee('Evidence for the decisions')
        ->assertSessionHas('locale', 'en')
        ->assertSee('<html lang="en"', false)
        ->assertDontSee('hreflang', false)
        ->assertInertia(fn (Assert $page) => $page->missing('site.locale'));
    expect(app(EffectiveSettings::class)->array('localization.enabled_locales'))->toBe(['en']);
});

it('redirects retired public language links to English with their query string', function (): void {
    $this->get('/am')->assertStatus(301)->assertRedirect('/');
    $this->get('/am/search?q=strategy')->assertStatus(301)->assertRedirect('/search?q=strategy');
    $this->post('/am/contact')->assertStatus(405);
    $this->get('/sitemaps/am.xml')->assertNotFound();
});

it('rejects unsupported locale prefixes', function (): void {
    $this->get('/fr/services')->assertNotFound();
    $this->getJson('/api/v1/search/suggestions?locale=am&q=strategy')->assertUnprocessable()->assertJsonValidationErrors('locale');
});

it('retires legacy preferences without deleting stored translations', function (): void {
    Locale::query()->create([
        'code' => 'am', 'name' => 'Amharic', 'native_name' => 'Amharic',
        'enabled' => true, 'is_default' => true, 'direction' => 'ltr', 'sort_order' => 2,
    ]);
    $user = User::factory()->create(['locale' => 'am']);
    $setting = Setting::query()->create([
        'key' => 'homepage.hero.slide_1.heading_am', 'scope' => 'global',
        'type' => 'string', 'value' => 'Historical translated heading',
    ]);
    $migration = require database_path('migrations/2026_10_03_140000_make_application_english_only.php');
    $migration->up();
    $migration->up();
    expect($user->fresh()->locale)->toBe('en')
        ->and(Locale::query()->where('code', 'am')->sole()->enabled)->toBeFalse()
        ->and($setting->fresh()->value)->toBe('Historical translated heading');
});

it('rejects Amharic values in staff and content administration', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    $administrator = User::factory()->create();
    $role = Role::query()->where('code', 'super_administrator')->sole();
    $administrator->roles()->attach($role);
    $this->actingAs($administrator)->withSession(privilegedSession($administrator))
        ->post('/admin/users/invitations', [
            'name' => 'English editor', 'email' => 'english-editor@example.test',
            'locale' => 'am', 'roles' => [$role->id],
        ])->assertSessionHasErrors('locale');
    $this->post('/admin/content', [
        'type' => 'insight', 'locale' => 'am', 'title' => 'Article',
        'slug' => 'article', 'summary' => 'Summary', 'body' => 'Article body',
    ])->assertSessionHasErrors('locale');
});
