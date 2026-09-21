<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Support\SettingCatalog;
use App\Support\Settings\EffectiveSettings;
use App\Support\Settings\SettingsConsumptionRegistry;
use App\Support\Settings\SettingsVerificationService;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    $this->seed(SettingSeeder::class);
    EffectiveSettings::flushCaches();
});

it('registers a consumer for every editable setting and keeps environment values read only', function (): void {
    foreach (array_keys(SettingCatalog::DEFINITIONS) as $key) {
        if (SettingCatalog::isEditable($key)) {
            expect(SettingsConsumptionRegistry::for($key))
                ->not->toBeNull("Editable setting [{$key}] has no consumer.");
        }

        if (isset(SettingCatalog::ENVIRONMENT_OVERRIDES[$key])) {
            expect(app(EffectiveSettings::class)->resolve($key)->isEditable)
                ->toBeFalse("Environment setting [{$key}] became editable.");
        }
    }
});

it('resolves explicit precedence and rejects invalid stored booleans', function (): void {
    Setting::query()->create([
        'key' => 'site.name',
        'type' => 'string',
        'scope' => 'locale:am',
        'value' => 'የተፅዕኖ አማካሪ',
    ]);

    $settings = app(EffectiveSettings::class);
    expect($settings->resolve('site.name', 'am')->value)->toBe('የተፅዕኖ አማካሪ')
        ->and($settings->resolve('site.name', 'am')->source)->toBe('Locale-scoped setting');

    Setting::query()->where('key', 'search.enabled')->where('scope', 'global')
        ->update(['value' => 'not-a-boolean']);
    EffectiveSettings::flushCaches();

    $resolved = app(EffectiveSettings::class)->resolve('search.enabled');
    expect($resolved->isValid)->toBeFalse()
        ->and($resolved->value)->toBeTrue()
        ->and(app(SettingsVerificationService::class)->verify()['summary']['error_count'])->toBeGreaterThan(0);
});

it('passes strict verification for a valid seeded registry', function (): void {
    expect(Artisan::call('settings:verify', ['--strict' => true, '--format' => 'json']))
        ->toBe(0);
});
