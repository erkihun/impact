<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\SettingCatalog;
use App\Support\Settings\EffectiveSettings;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, SettingSeeder::class]);
});

function settingsAdministrator(): User
{
    $administrator = User::factory()->create();
    $administrator->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());

    return $administrator;
}

function settingsPayloadFor(string $category, array $overrides = []): array
{
    $settings = app(EffectiveSettings::class);

    return collect(SettingCatalog::forCategory($category))
        ->reject(fn (array $definition, string $key): bool => ! $settings->resolve($key)->isEditable)
        ->mapWithKeys(fn (array $definition, string $key): array => [
            SettingCatalog::inputName($key) => match ($definition['type']) {
                'boolean' => (SettingCatalog::DEFAULT_VALUES[$key] ?? false) === true ? '1' : '0',
                default => SettingCatalog::DEFAULT_VALUES[$key] === null ? '' : (string) SettingCatalog::DEFAULT_VALUES[$key],
            },
        ])
        ->put('settings_version', $settings->categoryVersion($category))
        ->merge($overrides)
        ->all();
}

it('renders the settings overview and category control center', function (): void {
    $administrator = settingsAdministrator();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->get('/admin/settings')
        ->assertOk()
        ->assertSee('Settings Center')
        ->assertSee('Branding and Identity')
        ->assertSee('Appearance')
        ->assertSee('Homepage Hero')
        ->assertSee('Security')
        ->assertSee('Notifications')
        ->assertSee('Configuration history')
        ->assertSee('data-settings-icon="branding"', false)
        ->assertSee('data-settings-icon="homepage"', false)
        ->assertSee('data-settings-icon="notifications"', false)
        ->assertSee('data-settings-icon="history"', false)
        ->assertSee('data-settings-icon="diagnostics"', false);

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->get('/admin/settings/security')
        ->assertOk()
        ->assertSee('Security contact email')
        ->assertSee('Recent MFA')
        ->assertSee('Change reason')
        ->assertSee('data-settings-icon="security"', false)
        ->assertSee('x-data="settingsForm"', false)
        ->assertSee('aria-current="page"', false);

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->get('/admin/settings/performance')
        ->assertOk()
        ->assertSee('Cache')
        ->assertSee('Pagination')
        ->assertDontSee('htmlspecialchars(): Argument #1', false);
});

it('manages bilingual homepage hero slides, ordering, rotation, and browsed images', function (): void {
    Storage::fake('public');
    config()->set('filesystems.disks.public.url', 'http://localhost/storage');
    $administrator = settingsAdministrator();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->get('/admin/settings/homepage')
        ->assertOk()
        ->assertSee('Slider behavior')
        ->assertSee('Slide 1 heading — English')
        ->assertSee('Slide 1 heading — Amharic')
        ->assertSee('Slide 3 image')
        ->assertSee('type="file"', false)
        ->assertSee('data-settings-icon="homepage"', false);

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->put('/admin/settings/homepage', settingsPayloadFor('homepage', [
            'homepage__hero__autoplay' => '0',
            'homepage__hero__interval_seconds' => '9',
            'homepage__hero__slide_1__order' => '30',
            'homepage__hero__slide_1__heading_en' => 'Managed English hero statement.',
            'homepage__hero__slide_1__heading_am' => 'በአስተዳዳሪ የተቀናበረ የአማርኛ ዋና መልዕክት።',
            'homepage__hero__slide_1__image' => UploadedFile::fake()->image('hero-slide.webp', 1440, 900),
            'homepage__hero__slide_2__order' => '10',
            'change_reason' => 'Approved homepage hero rotation update.',
        ]))
        ->assertRedirect('/admin/settings/homepage');

    $storedImage = Setting::query()->where('key', 'homepage.hero.slide_1.image')->sole()->value;

    expect($storedImage)->toStartWith('/storage/homepage/homepage-hero-slide-1-image/')
        ->and(Setting::query()->where('key', 'homepage.hero.interval_seconds')->sole()->value)->toBe(9);

    Storage::disk('public')->assertExists(str_replace('/storage/', '', $storedImage));

    $this->assertDatabaseHas('audit_events', [
        'action' => 'settings.updated',
    ]);

    $this->get('/en')
        ->assertOk()
        ->assertSee('Managed English hero statement.')
        ->assertSee('data-slide-count="3"', false)
        ->assertSee('data-autoplay="false"', false)
        ->assertSee('data-interval="9000"', false)
        ->assertSee($storedImage, false);

    $this->get('/am')
        ->assertOk()
        ->assertSee('በአስተዳዳሪ የተቀናበረ የአማርኛ ዋና መልዕክት።')
        ->assertSee('x-data="homepageHeroSlider"', false);
});

it('rejects invalid homepage hero rotation settings', function (): void {
    $administrator = settingsAdministrator();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->from('/admin/settings/homepage')
        ->put('/admin/settings/homepage', settingsPayloadFor('homepage', [
            'homepage__hero__interval_seconds' => '2',
        ]))
        ->assertRedirect('/admin/settings/homepage')
        ->assertSessionHasErrors('homepage__hero__interval_seconds');
});

it('applies configured brand colors to the real admin shell palette', function (): void {
    $administrator = settingsAdministrator();

    foreach ([
        'branding.primary_color' => '#112233',
        'branding.advisory_teal' => '#445566',
        'branding.knowledge_blue' => '#778899',
        'branding.selective_gold' => '#AA7722',
        'branding.text_color' => '#101820',
        'branding.quiet_surface_color' => '#F0EEF2',
        'branding.default_border_color' => '#AABBCC',
    ] as $key => $value) {
        Setting::query()
            ->where('key', $key)
            ->where('scope', 'global')
            ->sole()
            ->update(['value' => $value]);
    }

    EffectiveSettings::flushCaches();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->get('/admin/settings')
        ->assertOk()
        ->assertSee('class="admin-sidebar hidden lg:flex"', false)
        ->assertSee('--palette-brand-900:17 34 51', false)
        ->assertSee('--palette-brand-950:12 24 36', false)
        ->assertSee('--palette-action-500:68 85 102', false)
        ->assertSee('--palette-action-700:46 58 69', false)
        ->assertSee('--palette-knowledge-600:119 136 153', false)
        ->assertSee('--palette-gold-500:170 119 34', false)
        ->assertSee('--palette-text:16 24 32', false)
        ->assertSee('--palette-surface-muted:240 238 242', false)
        ->assertSee('--palette-border:170 187 204', false);
});

it('updates one non-sensitive category, records reasons and updates real public UI behavior', function (): void {
    $administrator = settingsAdministrator();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->put('/admin/settings/general', settingsPayloadFor('general', [
            'site__name' => 'Impact Advisory Group',
            'site__short_name' => 'Advisory',
            'site__legal_name' => 'Impact Advisory Group PLC',
            'site__copyright_owner' => 'Impact Advisory Group PLC',
            'change_reason' => 'Approved organization identity update.',
        ]))
        ->assertRedirect('/admin/settings/general');

    expect(Setting::query()->where('key', 'site.name')->sole()->value)->toBe('Impact Advisory Group')
        ->and(Setting::query()->where('key', 'site.name')->sole()->change_reason)->toBe('Approved organization identity update.');

    $this->assertDatabaseHas('audit_events', ['action' => 'settings.updated']);

    $this->get('/en')
        ->assertOk()
        ->assertSee('Impact Advisory Group PLC');
});

it('rejects unknown setting keys and environment-managed overrides', function (): void {
    $administrator = settingsAdministrator();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->from('/admin/settings/general')
        ->put('/admin/settings/general', settingsPayloadFor('general', [
            'site__timezone' => 'UTC',
            'unknown__setting' => 'not allowed',
        ]))
        ->assertRedirect('/admin/settings/general')
        ->assertSessionHasErrors('settings');
});

it('requires recent MFA and change reason for sensitive categories', function (): void {
    $administrator = settingsAdministrator();
    $staleSession = privilegedSession($administrator);
    $staleSession['mfa_verified_at'] = now('UTC')->subMinutes(30)->timestamp;

    $this->actingAs($administrator)
        ->withSession($staleSession)
        ->put('/admin/settings/security', settingsPayloadFor('security', [
            'security__recent_mfa_valid_minutes' => '20',
            'change_reason' => 'Updating security review cadence.',
        ]))
        ->assertRedirect(route('mfa.show'));

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->from('/admin/settings/security')
        ->put('/admin/settings/security', settingsPayloadFor('security', [
            'security__recent_mfa_valid_minutes' => '20',
            'change_reason' => '',
        ]))
        ->assertRedirect('/admin/settings/security')
        ->assertSessionHasErrors('change_reason');
});

it('updates sensitive settings with recent MFA and shows redacted safe history', function (): void {
    $administrator = settingsAdministrator();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->put('/admin/settings/security', settingsPayloadFor('security', [
            'security__recent_mfa_valid_minutes' => '20',
            'change_reason' => 'Approved quarterly security control review.',
        ]))
        ->assertRedirect('/admin/settings/security');

    $event = AuditEvent::query()
        ->where('action', 'settings.updated')
        ->where('metadata->key', 'security.recent_mfa_valid_minutes')
        ->latest()
        ->firstOrFail();

    expect(data_get($event->metadata, 'category'))->toBe('security')
        ->and(data_get($event->metadata, 'change_reason'))->toBe('Approved quarterly security control review.')
        ->and(data_get($event->metadata, 'new_value'))->toBe('20');

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->get('/admin/settings/history?category=security')
        ->assertOk()
        ->assertSee('Configuration history')
        ->assertSee('Approved quarterly security control review.');
});

it('uses browsed image uploads for branding identity assets instead of typed urls', function (): void {
    Storage::fake('public');
    config()->set('filesystems.disks.public.url', 'http://localhost/storage');
    $administrator = settingsAdministrator();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->get('/admin/settings/branding')
        ->assertOk()
        ->assertSee('Primary logo image')
        ->assertSee('type="file"', false)
        ->assertDontSee('Primary logo URL');

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->put('/admin/settings/branding', settingsPayloadFor('branding', [
            'branding__logo_url' => UploadedFile::fake()->image('approved-logo.png', 320, 120),
            'change_reason' => 'Approved visual identity asset upload.',
        ]))
        ->assertRedirect('/admin/settings/branding');

    $stored = Setting::query()->where('key', 'branding.logo_url')->sole()->value;

    expect($stored)->toStartWith('/storage/branding/branding-logo-url/')
        ->and($stored)->toEndWith('.png');

    Storage::disk('public')->assertExists(str_replace('/storage/', '', $stored));
});

it('normalizes legacy branding asset urls to the current host path', function (): void {
    $this->seed(SettingSeeder::class);
    $administrator = settingsAdministrator();

    Setting::query()
        ->where('key', 'branding.logo_url')
        ->where('scope', 'global')
        ->sole()
        ->update(['value' => 'http://localhost/storage/branding/legacy-logo.png']);

    EffectiveSettings::flushCaches();

    expect(app(EffectiveSettings::class)->mediaReference('branding.logo_url'))
        ->toBe('/storage/branding/legacy-logo.png');

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->get('/admin/settings/branding')
        ->assertOk()
        ->assertSee('src="/storage/branding/legacy-logo.png"', false)
        ->assertDontSee('src="http://localhost/storage/branding/legacy-logo.png"', false);
});

it('resets only the selected category defaults', function (): void {
    $administrator = settingsAdministrator();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->put('/admin/settings/notifications', settingsPayloadFor('notifications', [
            'notifications__engagement_submissions' => '0',
            'change_reason' => 'Testing engagement notification default.',
        ]))
        ->assertRedirect('/admin/settings/notifications');

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->post('/admin/settings/notifications/reset', [
            'category' => 'notifications',
            'confirm_category' => 'notifications',
            'settings_version' => app(EffectiveSettings::class)->categoryVersion('notifications'),
            'change_reason' => 'Restore notification settings after test.',
        ])
        ->assertRedirect('/admin/settings/notifications');

    expect(Setting::query()->where('key', 'notifications.engagement_submissions')->sole()->value)->toBeTrue();
});

it('rejects a stale settings group version without overwriting the current value', function (): void {
    $administrator = settingsAdministrator();
    $staleVersion = app(EffectiveSettings::class)->categoryVersion('general');

    Setting::query()
        ->where('key', 'site.name')
        ->sole()
        ->update([
            'value' => 'Changed by another administrator',
            'version' => 2,
        ]);

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->put('/admin/settings/general', settingsPayloadFor('general', [
            'settings_version' => $staleVersion,
            'site__name' => 'Stale overwrite',
            'change_reason' => 'Attempting an outdated settings update.',
        ]))
        ->assertStatus(409)
        ->assertSee('These settings changed after you opened them.');

    expect(Setting::query()->where('key', 'site.name')->sole()->value)
        ->toBe('Changed by another administrator');
});

it('denies settings changes without permission', function (): void {
    $this->actingAs(User::factory()->create())
        ->put('/admin/settings/general', [])
        ->assertForbidden();
});
