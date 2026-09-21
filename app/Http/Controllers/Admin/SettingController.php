<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\StoreSettingAssetAction;
use App\Actions\Settings\UpdateSettingsAction;
use App\Data\Settings\UpdateSettingsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetSettingsGroupRequest;
use App\Http\Requests\Admin\UpdateSettingsGroupRequest;
use App\Models\AuditEvent;
use App\Models\User;
use App\Support\CorrelationContext;
use App\Support\SettingCatalog;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

final class SettingController extends Controller
{
    public function index(Request $request, EffectiveSettings $settings): View
    {
        $search = trim($request->string('q')->toString());
        $recentChanges = AuditEvent::query()
            ->with('actor:id,name')
            ->whereIn('action', ['settings.updated', 'settings.reset'])
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.settings.index', [
            'categories' => $this->categoryCards($settings),
            'environment' => SettingCatalog::environmentSnapshot(),
            'lastChange' => $recentChanges->first(),
            'navigationGroups' => SettingCatalog::navigationGroups(),
            'recentChanges' => $recentChanges,
            'search' => $search,
            'searchResults' => $search === '' ? collect() : $this->searchResults($search, $settings),
            'settings' => $settings,
        ]);
    }

    public function show(string $category, EffectiveSettings $settings): View
    {
        abort_unless(SettingCatalog::categoryExists($category), 404);

        $definitions = SettingCatalog::forCategory($category);

        return view('admin.settings.show', [
            'category' => $category,
            'categoryDefinition' => SettingCatalog::categoryDefinition($category),
            'definitions' => $definitions,
            'environment' => SettingCatalog::environmentSnapshot(),
            'groupedDefinitions' => collect($definitions)->groupBy('group', preserveKeys: true),
            'navigationGroups' => SettingCatalog::navigationGroups(),
            'requiresReason' => SettingCatalog::categoryRequiresReason($category),
            'requiresRecentMfa' => SettingCatalog::categoryRequiresRecentMfa($category),
            'settingsVersion' => $settings->categoryVersion($category),
            'settings' => $settings,
            'statuses' => collect(array_keys($definitions))
                ->mapWithKeys(fn (string $key): array => [$key => $settings->safeStatus($key)]),
        ]);
    }

    public function update(
        UpdateSettingsGroupRequest $request,
        UpdateSettingsAction $action,
        StoreSettingAssetAction $assets,
        CorrelationContext $correlation,
        EffectiveSettings $settings,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $category = $request->category();
        $values = [];

        foreach (SettingCatalog::forCategory($category) as $key => $definition) {
            if (! $settings->resolve($key)->isEditable) {
                continue;
            }

            $input = SettingCatalog::inputName($key);
            $values[$key] = $this->castInputValue($request, $input, $definition['type'], $key, $settings, $assets);
        }

        $action->execute($actor, new UpdateSettingsData(
            values: $values,
            category: $category,
            actorId: $actor->id,
            correlationId: $correlation->id(),
            expectedVersion: $request->string('settings_version')->toString(),
            changeReason: $request->filled('change_reason') ? $request->string('change_reason')->toString() : null,
        ));

        return redirect()
            ->route('admin.settings.show', ['category' => $category])
            ->with('status', __('Settings updated.'));
    }

    public function reset(
        ResetSettingsGroupRequest $request,
        UpdateSettingsAction $action,
        CorrelationContext $correlation,
        EffectiveSettings $settings,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $category = $request->category();
        $values = [];

        foreach (SettingCatalog::forCategory($category) as $key => $definition) {
            if (! $settings->resolve($key)->isEditable) {
                continue;
            }

            $values[$key] = SettingCatalog::DEFAULT_VALUES[$key] ?? null;
        }

        $action->execute($actor, new UpdateSettingsData(
            values: $values,
            category: $category,
            actorId: $actor->id,
            correlationId: $correlation->id(),
            expectedVersion: $request->string('settings_version')->toString(),
            changeReason: $request->filled('change_reason') ? $request->string('change_reason')->toString() : __('Category defaults restored.'),
            reset: true,
        ));

        return redirect()
            ->route('admin.settings.show', ['category' => $category])
            ->with('status', __('Settings restored to category defaults.'));
    }

    private function categoryCards(EffectiveSettings $settings): Collection
    {
        return collect(SettingCatalog::CATEGORIES)
            ->reject(fn (array $definition, string $category): bool => $category === 'environment')
            ->map(function (array $definition, string $category) use ($settings): array {
                $keys = SettingCatalog::keysForCategory($category);
                $incomplete = collect($keys)
                    ->filter(fn (string $key): bool => in_array($settings->effective($key), [null, ''], true))
                    ->count();

                return [
                    ...$definition,
                    'category' => $category,
                    'controls' => count($keys),
                    'incomplete' => $incomplete,
                    'requires_recent_mfa' => SettingCatalog::categoryRequiresRecentMfa($category),
                    'requires_reason' => SettingCatalog::categoryRequiresReason($category),
                ];
            });
    }

    private function searchResults(string $search, EffectiveSettings $settings): Collection
    {
        $needle = mb_strtolower($search);

        return collect(SettingCatalog::DEFINITIONS)
            ->filter(function (array $definition, string $key) use ($needle): bool {
                return str_contains(mb_strtolower($key), $needle)
                    || str_contains(mb_strtolower($definition['label']), $needle)
                    || str_contains(mb_strtolower($definition['description']), $needle)
                    || str_contains(mb_strtolower($definition['category']), $needle)
                    || str_contains(mb_strtolower($definition['group']), $needle);
            })
            ->map(fn (array $definition, string $key): array => [
                'key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'category' => $definition['category'],
                'category_label' => SettingCatalog::categoryDefinition($definition['category'])['label'],
                'category_icon' => SettingCatalog::categoryDefinition($definition['category'])['icon'],
                'status' => $settings->safeStatus($key),
            ])
            ->values();
    }

    private function castInputValue(
        Request $request,
        string $input,
        string $type,
        string $key,
        EffectiveSettings $settings,
        StoreSettingAssetAction $assets,
    ): bool|int|string|null {
        return match ($type) {
            'boolean' => $request->boolean($input),
            'integer', 'duration' => $request->integer($input),
            'media_reference' => ($file = $request->file($input)) instanceof UploadedFile
                ? $assets->execute($key, $file)
                : $settings->mediaReference($key),
            default => $request->filled($input) ? $request->string($input)->toString() : null,
        };
    }
}
