<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Settings\UpdateSettingsData;
use App\Events\SettingsGroupUpdated;
use App\Exceptions\SettingsVersionConflictException;
use App\Models\Setting;
use App\Models\User;
use App\Support\SettingCatalog;
use App\Support\Settings\EffectiveSettings;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateSettingsAction
{
    public function __construct(
        private AuditRecorder $audit,
        private EffectiveSettings $settings,
    ) {}

    public function execute(User $actor, UpdateSettingsData $data): void
    {
        if (! $actor->hasPermission('settings.manage')) {
            throw new AuthorizationException;
        }

        DB::transaction(function () use ($data): void {
            Setting::query()
                ->whereIn('key', SettingCatalog::keysForCategory($data->category))
                ->where('scope', 'global')
                ->lockForUpdate()
                ->get();

            if (! hash_equals($data->expectedVersion, $this->settings->categoryVersion($data->category))) {
                throw new SettingsVersionConflictException($data->category);
            }

            $changedKeys = [];
            $effects = [];

            foreach ($data->values as $key => $value) {
                $definition = SettingCatalog::DEFINITIONS[$key];
                if (! $this->settings->resolve($key)->isEditable) {
                    continue;
                }

                $setting = Setting::query()->firstOrNew(['key' => $key, 'scope' => 'global']);
                $beforeHash = $setting->exists ? hash('sha256', $setting->toJson()) : null;
                $beforeValue = $setting->exists ? $setting->value : (SettingCatalog::DEFAULT_VALUES[$key] ?? null);
                if ($setting->exists && $beforeValue === $value) {
                    continue;
                }

                $setting->fill([
                    'type' => $definition['type'],
                    'value' => $value,
                    'updated_by' => $data->actorId,
                    'change_reason' => $data->changeReason,
                    'last_effective_at' => now('UTC'),
                    'version' => $setting->exists ? ((int) $setting->version) + 1 : 1,
                ])->save();

                $changedKeys[] = $key;
                $effects = [
                    ...$effects,
                    ...($definition['effects'] ?? ['immediate']),
                ];

                $this->audit->record(new AuditData(
                    action: $data->reset ? 'settings.reset' : 'settings.updated',
                    auditableType: Setting::class,
                    auditableId: $setting->id,
                    actorId: $data->actorId,
                    correlationId: $data->correlationId,
                    beforeHash: $beforeHash,
                    afterHash: hash('sha256', $setting->fresh()->toJson()),
                    metadata: [
                        'key' => $key,
                        'category' => $data->category,
                        'label' => $definition['label'],
                        'classification' => $definition['audit'] ?? ($definition['sensitivity'] ?? 'internal'),
                        'change_reason' => $data->changeReason,
                        'previous_value' => $this->settings->safeDisplayValue($key, $beforeValue),
                        'new_value' => $this->settings->safeDisplayValue($key, $value),
                        'effects' => $definition['effects'] ?? ['immediate'],
                        'reset' => $data->reset,
                    ],
                ));
            }

            if ($changedKeys !== []) {
                SettingsGroupUpdated::dispatch(
                    category: $data->category,
                    keys: $changedKeys,
                    effects: array_values(array_unique($effects)),
                    actorId: $data->actorId,
                    occurredAt: CarbonImmutable::now('UTC'),
                );
            }
        }, attempts: 3);

        EffectiveSettings::flushCaches();
    }
}
