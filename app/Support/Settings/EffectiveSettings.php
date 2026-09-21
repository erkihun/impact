<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Data\Settings\EffectiveSettingValue;
use App\Enums\Settings\SettingEffect;
use App\Models\Setting;
use App\Support\SettingCatalog;
use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;

final readonly class EffectiveSettings
{
    public const CACHE_PREFIX = 'settings.resolved';

    public function get(string $key): bool|int|float|string|array|null
    {
        return $this->all()->get($key);
    }

    /** @return Collection<string, bool|int|float|string|array<mixed>|null> */
    public function all(): Collection
    {
        return collect(Cache::remember(
            self::CACHE_PREFIX.'.global',
            now()->addMinutes($this->cacheLifetimeMinutes()),
            function (): array {
                $stored = Setting::query()
                    ->where('scope', 'global')
                    ->get(['key', 'value'])
                    ->keyBy('key');

                return collect(SettingCatalog::DEFAULT_VALUES)
                    ->map(function (mixed $default, string $key) use ($stored): mixed {
                        $value = $stored->get($key)?->value ?? $default;

                        try {
                            return $this->normalize($key, $value);
                        } catch (RuntimeException) {
                            return $this->normalize($key, $default);
                        }
                    })
                    ->all();
            },
        ));
    }

    public function effective(string $key): bool|int|float|string|array|null
    {
        return $this->resolve($key)->value;
    }

    public function resolve(string $key, ?string $locale = null): EffectiveSettingValue
    {
        $definition = SettingCatalog::DEFINITIONS[$key] ?? null;
        if ($definition === null) {
            throw new InvalidArgumentException("Unknown setting [{$key}].");
        }

        $record = null;
        $source = 'Registry default';
        $rawValue = SettingCatalog::DEFAULT_VALUES[$key] ?? null;
        $overridden = false;
        $editable = SettingCatalog::isEditable($key);
        $warning = null;

        if ($this->isEnvironmentManaged($key)) {
            $environmentValue = $this->configuredEnvironmentValue($key);
            $rawValue = $environmentValue ?? $rawValue;
            $source = $environmentValue === null
                ? 'Managed by environment (safe default)'
                : 'Managed by environment';
            $overridden = true;
            $editable = false;
            $warning = $environmentValue === null
                ? 'No environment value is configured; the registry safe default is active.'
                : null;
        } else {
            $environmentScope = 'environment:'.app()->environment();
            $localeScope = 'locale:'.($locale ?? app()->getLocale());
            $record = Setting::query()
                ->where('key', $key)
                ->whereIn('scope', [$environmentScope, $localeScope, 'global'])
                ->get()
                ->sortBy(fn (Setting $setting): int => match ($setting->scope) {
                    $environmentScope => 1,
                    $localeScope => 2,
                    'global' => 3,
                    default => 4,
                })
                ->first();

            if ($record instanceof Setting) {
                $rawValue = $record->value;
                $source = match ($record->scope) {
                    $environmentScope => 'Environment-scoped setting',
                    $localeScope => 'Locale-scoped setting',
                    default => 'Global database setting',
                };
                $overridden = true;
            }

            if (! $editable) {
                $source .= ' (read-only pending approved consumer)';
                $warning = 'This compatibility value is not editable until an approved runtime consumer is implemented.';
            }
        }

        $isValid = true;
        try {
            $value = $this->normalize($key, $rawValue);
        } catch (RuntimeException $exception) {
            $value = $this->normalize($key, SettingCatalog::DEFAULT_VALUES[$key] ?? null);
            $source = 'Registry default (invalid stored value rejected)';
            $isValid = false;
            $warning = $exception->getMessage();
        }

        $effects = SettingCatalog::effects($key);

        return new EffectiveSettingValue(
            key: $key,
            value: $value,
            source: $source,
            isOverridden: $overridden,
            isEditable: $editable,
            requiresRestart: collect($effects)->contains(
                fn (SettingEffect $effect): bool => in_array($effect, [
                    SettingEffect::RequiresApplicationRestart,
                    SettingEffect::RequiresQueueRestart,
                    SettingEffect::RequiresSchedulerRestart,
                ], true),
            ),
            requiresDeployment: in_array(SettingEffect::RequiresDeployment, $effects, true),
            updatedAt: $record?->updated_at instanceof CarbonImmutable
                ? $record->updated_at
                : ($record?->updated_at?->toImmutable()),
            isValid: $isValid,
            warning: $warning,
        );
    }

    public function environmentValue(string $key): bool|int|float|string|array|null
    {
        if (! $this->isEnvironmentManaged($key)) {
            return null;
        }

        return $this->resolve($key)->value;
    }

    public function isEnvironmentManaged(string $key): bool
    {
        return array_key_exists($key, SettingCatalog::ENVIRONMENT_OVERRIDES);
    }

    /**
     * @return array{
     *     stored: string,
     *     effective: string,
     *     source: string,
     *     environment_managed: bool,
     *     editable: bool,
     *     overridden: bool,
     *     requires_restart: bool,
     *     requires_deployment: bool,
     *     valid: bool,
     *     warning: string|null
     * }
     */
    public function safeStatus(string $key): array
    {
        $resolved = $this->resolve($key);

        return [
            'stored' => $this->safeDisplayValue($key, $this->get($key)),
            'effective' => $this->safeDisplayValue($key, $resolved->value),
            'source' => $resolved->source,
            'environment_managed' => $this->isEnvironmentManaged($key),
            'editable' => $resolved->isEditable,
            'overridden' => $resolved->isOverridden,
            'requires_restart' => $resolved->requiresRestart,
            'requires_deployment' => $resolved->requiresDeployment,
            'valid' => $resolved->isValid,
            'warning' => $resolved->warning,
        ];
    }

    public function safeDisplayValue(string $key, mixed $value): string
    {
        if (($definition = SettingCatalog::DEFINITIONS[$key] ?? null) === null) {
            return 'Unknown';
        }

        if (($definition['sensitivity'] ?? null) === 'secret_reference') {
            return filled($value) ? 'Configured' : 'Not configured';
        }

        if (is_bool($value)) {
            return $value ? 'Enabled' : 'Disabled';
        }

        if ($value === null || $value === '') {
            return 'Not set';
        }

        if (is_array($value)) {
            return implode(', ', array_map(static fn (mixed $item): string => (string) $item, $value));
        }

        return (string) $value;
    }

    public function string(string $key): string
    {
        $value = $this->effective($key);
        if (! is_string($value)) {
            throw new RuntimeException("Setting [{$key}] is not a string.");
        }

        return $value;
    }

    public function nullableString(string $key): ?string
    {
        $value = $this->effective($key);
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new RuntimeException("Setting [{$key}] is not a nullable string.");
    }

    public function boolean(string $key): bool
    {
        $value = $this->effective($key);
        if (! is_bool($value)) {
            throw new RuntimeException("Setting [{$key}] is not a boolean.");
        }

        return $value;
    }

    public function integer(string $key): int
    {
        $value = $this->effective($key);
        if (! is_int($value)) {
            throw new RuntimeException("Setting [{$key}] is not an integer.");
        }

        return $value;
    }

    public function decimal(string $key): float
    {
        $value = $this->effective($key);
        if (! is_float($value) && ! is_int($value)) {
            throw new RuntimeException("Setting [{$key}] is not a decimal.");
        }

        return (float) $value;
    }

    public function duration(string $key): int
    {
        return $this->integer($key);
    }

    /** @template TEnum of BackedEnum
     * @param  class-string<TEnum>  $enum
     * @return TEnum
     */
    public function enum(string $key, string $enum): BackedEnum
    {
        $value = $this->string($key);
        $resolved = $enum::tryFrom($value);
        if (! $resolved instanceof BackedEnum) {
            throw new RuntimeException("Setting [{$key}] contains an unknown enum value.");
        }

        return $resolved;
    }

    /** @return array<mixed> */
    public function array(string $key): array
    {
        $value = $this->effective($key);
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && in_array(SettingCatalog::type($key)->value, ['locale_list', 'multi_select'], true)) {
            return collect(explode(',', $value))
                ->map(static fn (string $item): string => trim($item))
                ->filter()
                ->values()
                ->all();
        }

        throw new RuntimeException("Setting [{$key}] is not an array.");
    }

    public function mediaReference(string $key): ?string
    {
        $reference = $this->nullableString($key);
        if ($reference === null) {
            return null;
        }

        if (str_starts_with($reference, 'storage/')) {
            return '/'.$reference;
        }

        $path = parse_url($reference, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/storage/')) {
            return $path;
        }

        return $reference;
    }

    public function localeSpecificText(string $key, string $locale): ?string
    {
        return $this->resolve($key, $locale)->value === null
            ? null
            : $this->resolve($key, $locale)->value;
    }

    public function safeSecretStatus(string $key): string
    {
        if (SettingCatalog::sensitivity($key)->value !== 'secret_reference') {
            throw new RuntimeException("Setting [{$key}] is not a secret reference.");
        }

        return filled($this->effective($key)) ? 'Configured' : 'Not configured';
    }

    public function categoryVersion(string $category): string
    {
        $keys = SettingCatalog::keysForCategory($category);
        $versions = Setting::query()
            ->whereIn('key', $keys)
            ->whereIn('scope', [
                'global',
                'locale:'.app()->getLocale(),
                'environment:'.app()->environment(),
            ])
            ->orderBy('key')
            ->orderBy('scope')
            ->get(['key', 'scope', 'version', 'updated_at'])
            ->map(fn (Setting $setting): array => [
                $setting->key,
                $setting->scope,
                $setting->version,
                $setting->updated_at?->toISOString(),
            ])
            ->all();

        return hash('sha256', json_encode($versions, JSON_THROW_ON_ERROR));
    }

    public static function flushCaches(): void
    {
        Cache::forget(self::CACHE_PREFIX.'.global');
    }

    private function configuredEnvironmentValue(string $key): mixed
    {
        if ($key === 'seo.robots_indexing') {
            return app()->environment('production');
        }

        $path = SettingCatalog::ENVIRONMENT_OVERRIDES[$key] ?? null;

        return $path === null ? null : config($path);
    }

    private function cacheLifetimeMinutes(): int
    {
        $configured = Setting::query()
            ->where('key', 'performance.settings_cache_lifetime_minutes')
            ->where('scope', 'global')
            ->value('value');
        $decoded = is_string($configured) ? json_decode($configured, true) : $configured;

        return is_int($decoded) && $decoded >= 1 && $decoded <= 1440
            ? $decoded
            : (int) SettingCatalog::DEFAULT_VALUES['performance.settings_cache_lifetime_minutes'];
    }

    private function normalize(string $key, mixed $value): bool|int|float|string|array|null
    {
        if ($value === null) {
            $rules = SettingCatalog::DEFINITIONS[$key]['rule'] ?? [];
            if (in_array('nullable', $rules, true)) {
                return null;
            }

            throw new RuntimeException("Setting [{$key}] cannot be null.");
        }

        return match (SettingCatalog::DEFINITIONS[$key]['type'] ?? 'string') {
            'boolean' => $this->normalizeBoolean($key, $value),
            'integer', 'duration' => $this->normalizeInteger($key, $value),
            'decimal' => $this->normalizeDecimal($key, $value),
            'multi_select' => $this->normalizeArray($key, $value),
            default => $this->normalizeString($key, $value),
        };
    }

    private function normalizeBoolean(string $key, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) && in_array($value, [0, 1], true)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return match (strtolower($value)) {
                'true', '1' => true,
                'false', '0' => false,
                default => throw new RuntimeException("Setting [{$key}] contains an invalid boolean."),
            };
        }

        throw new RuntimeException("Setting [{$key}] contains an invalid boolean.");
    }

    private function normalizeInteger(string $key, mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw new RuntimeException("Setting [{$key}] contains an invalid integer.");
    }

    private function normalizeDecimal(string $key, mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw new RuntimeException("Setting [{$key}] contains an invalid decimal.");
    }

    /** @return array<mixed> */
    private function normalizeArray(string $key, mixed $value): array
    {
        if (! is_array($value) || ! Arr::isList($value)) {
            throw new RuntimeException("Setting [{$key}] contains an invalid list.");
        }

        return $value;
    }

    private function normalizeString(string $key, mixed $value): string
    {
        if (! is_string($value)) {
            throw new RuntimeException("Setting [{$key}] contains an invalid string.");
        }

        return $value;
    }
}
