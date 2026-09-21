<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Notifications\Engagement\EngagementReceivedNotification;
use App\Support\Settings\EffectiveSettings;
use App\Support\Settings\PasswordPolicy;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Validator;

beforeEach(function (): void {
    $this->seed(SettingSeeder::class);
    EffectiveSettings::flushCaches();
});

function setRuntimeSetting(string $key, mixed $value): void
{
    Setting::query()
        ->where('key', $key)
        ->where('scope', 'global')
        ->sole()
        ->update(['value' => $value]);
    EffectiveSettings::flushCaches();
}

it('renders changed branding, identity, appearance, and SEO settings on the real public page', function (): void {
    setRuntimeSetting('site.legal_name', 'Runtime Integrated PLC');
    setRuntimeSetting('branding.primary_color', '#112233');
    setRuntimeSetting('appearance.card_radius', 'medium');
    setRuntimeSetting('seo.default_title_suffix', 'Verified suffix');

    $this->get('/en')
        ->assertOk()
        ->assertSee('Runtime Integrated PLC')
        ->assertSee('--color-primary-900:#112233', false)
        ->assertSee('--palette-brand-900:17 34 51', false)
        ->assertSee('--palette-brand-950:12 24 36', false)
        ->assertSee('--setting-card-radius:0.5rem', false)
        ->assertSee('data-default-theme', false);
});

it('blocks disabled public forms, search, sitemap, and maintenance status routes', function (): void {
    setRuntimeSetting('engagement.consultation_form_enabled', false);
    $this->get('/en/consultation')->assertNotFound();
    $this->post('/en/consultation-requests')->assertNotFound();

    setRuntimeSetting('search.enabled', false);
    $this->get('/en/search')->assertNotFound();

    setRuntimeSetting('seo.sitemap_enabled', false);
    $this->get('/sitemap.xml')->assertNotFound();

    setRuntimeSetting('maintenance.allow_status_page', false);
    $this->get('/status')->assertNotFound();
});

it('applies configured password policy and notification suppression', function (): void {
    setRuntimeSetting('authentication.password_min_length', 16);
    setRuntimeSetting('authentication.require_number', true);

    $validator = Validator::make(
        ['password' => 'OnlyLettersPassword'],
        ['password' => app(PasswordPolicy::class)->rules()],
    );
    expect($validator->fails())->toBeTrue();

    setRuntimeSetting('notifications.engagement_submissions', false);
    $notification = new EngagementReceivedNotification('Aster', 'ICO-TEST');
    expect($notification->via(new stdClass))->toBe([]);
});
