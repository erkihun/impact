<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Enums\Settings\SettingType;
use App\Models\Setting;
use App\Support\SettingCatalog;
use Illuminate\Support\Facades\Schema;
use Throwable;

final readonly class SettingsVerificationService
{
    public function __construct(private EffectiveSettings $settings) {}

    /**
     * @return array{
     *     generated_at: string,
     *     summary: array<string, int>,
     *     issues: list<array{severity: string, code: string, key: string|null, message: string}>,
     *     settings: list<array<string, mixed>>
     * }
     */
    public function verify(): array
    {
        $issues = [];
        $rows = [];
        $stored = Schema::hasTable('settings')
            ? Setting::query()->get(['key', 'scope', 'type', 'value', 'updated_at'])->groupBy('key')
            : collect();
        $translations = $this->amharicTranslations();

        foreach ($stored->keys()->diff(array_keys(SettingCatalog::DEFINITIONS)) as $unknownKey) {
            $issues[] = $this->issue('error', 'UNKNOWN_DATABASE_KEY', (string) $unknownKey, 'Stored key is not registered.');
        }

        foreach (SettingCatalog::DEFINITIONS as $key => $definition) {
            $consumer = SettingsConsumptionRegistry::for($key);
            $resolved = $this->settings->resolve($key);
            $records = $stored->get($key, collect());

            if (! array_key_exists($key, SettingCatalog::DEFAULT_VALUES)) {
                $issues[] = $this->issue('error', 'MISSING_DEFAULT', $key, 'Registry default is missing.');
            }
            if (SettingType::tryFrom($definition['type']) === null) {
                $issues[] = $this->issue('error', 'INVALID_TYPE', $key, 'Registry type is not supported.');
            }
            if (($definition['rule'] ?? []) === []) {
                $issues[] = $this->issue('error', 'MISSING_VALIDATION', $key, 'Validation rules are missing.');
            }
            if (SettingCatalog::isEditable($key) && $consumer === null) {
                $issues[] = $this->issue('error', 'MISSING_CONSUMER', $key, 'Editable setting has no approved runtime consumer.');
            }
            if (! $resolved->isValid) {
                $issues[] = $this->issue('error', 'INVALID_EFFECTIVE_VALUE', $key, $resolved->warning ?? 'Effective value is invalid.');
            }
            foreach ($records as $record) {
                if (! $this->storedTypeMatchesDefinition($record->type, $definition['type'])) {
                    $issues[] = $this->issue('error', 'STORED_TYPE_MISMATCH', $key, "Stored type [{$record->type}] does not match the registry.");
                }
            }
            if (! isset($translations[$definition['label']])) {
                $issues[] = $this->issue('warning', 'MISSING_AMHARIC_LABEL', $key, 'Amharic label translation is missing.');
            }
            if (! isset($translations[$definition['description']])) {
                $issues[] = $this->issue('warning', 'MISSING_AMHARIC_HELP', $key, 'Amharic help translation is missing.');
            }

            $rows[] = [
                'key' => $key,
                'category' => $definition['category'],
                'type' => $definition['type'],
                'default' => $this->settings->safeDisplayValue($key, SettingCatalog::DEFAULT_VALUES[$key] ?? null),
                'stored' => $this->settings->safeStatus($key)['stored'],
                'effective' => $this->settings->safeStatus($key)['effective'],
                'source' => $resolved->source,
                'editable' => $resolved->isEditable,
                'consumer' => $consumer['consumer'] ?? null,
                'consumer_file' => $consumer['file'] ?? null,
                'behavior' => $consumer['behavior'] ?? 'Compatibility value retained read-only.',
                'requires_restart' => $resolved->requiresRestart,
                'requires_deployment' => $resolved->requiresDeployment,
                'valid' => $resolved->isValid,
                'status' => $resolved->isEditable
                    ? 'Fully integrated and verified'
                    : ($this->settings->isEnvironmentManaged($key)
                        ? 'Environment-controlled'
                        : 'Obsolete/read-only pending consumer'),
            ];
        }

        $enabledLocales = $this->settings->array('localization.enabled_locales');
        $defaultLocale = $this->settings->string('localization.default_locale');
        if ($enabledLocales === [] || ! in_array($defaultLocale, $enabledLocales, true)) {
            $issues[] = $this->issue('error', 'DISABLED_DEFAULT_LOCALE', 'localization.enabled_locales', 'The active default locale must remain enabled.');
        }

        $featureReviewDate = $this->settings->nullableString('features.review_date');
        if ($featureReviewDate !== null && now('UTC')->startOfDay()->gt($featureReviewDate)) {
            $issues[] = $this->issue('warning', 'EXPIRED_FEATURE_REVIEW', 'features.review_date', 'Feature flags are past their review date.');
        }

        $errors = collect($issues)->where('severity', 'error')->count();
        $warnings = collect($issues)->where('severity', 'warning')->count();

        return [
            'generated_at' => now('UTC')->toIso8601String(),
            'summary' => [
                'registry_count' => count(SettingCatalog::DEFINITIONS),
                'stored_key_count' => $stored->count(),
                'effective_value_count' => count($rows),
                'editable_count' => collect($rows)->where('editable', true)->count(),
                'environment_override_count' => count(SettingCatalog::ENVIRONMENT_OVERRIDES),
                'read_only_compatibility_count' => count(SettingCatalog::INACTIVE_KEYS),
                'invalid_count' => collect($rows)->where('valid', false)->count(),
                'missing_consumer_count' => collect($issues)->where('code', 'MISSING_CONSUMER')->count(),
                'error_count' => $errors,
                'warning_count' => $warnings,
            ],
            'issues' => $issues,
            'settings' => $rows,
        ];
    }

    /** @return array{severity: string, code: string, key: string|null, message: string} */
    private function issue(string $severity, string $code, ?string $key, string $message): array
    {
        return compact('severity', 'code', 'key', 'message');
    }

    private function storedTypeMatchesDefinition(string $storedType, string $definitionType): bool
    {
        if ($storedType === $definitionType) {
            return true;
        }

        return $storedType === 'string'
            && in_array($definitionType, [
                'color',
                'email',
                'enum',
                'locale',
                'phone',
                'text',
                'timezone',
                'url',
            ], true);
    }

    /** @return array<string, string> */
    private function amharicTranslations(): array
    {
        try {
            $translations = json_decode(
                (string) file_get_contents(lang_path('am.json')),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

            return is_array($translations) ? $translations : [];
        } catch (Throwable) {
            return [];
        }
    }
}
