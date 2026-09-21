<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Settings Center')"
            :title="__($categoryDefinition['label'])"
            :description="__($categoryDefinition['description'])"
        />
    </x-slot>

    <div class="admin-workspace">
        @if (session('status'))
            <div class="status-success mb-6" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-summary mb-6" data-error-summary tabindex="-1" role="alert">
                <p class="font-bold">{{ __('These settings were not saved.') }}</p>
                <ul class="mt-2 grid gap-1">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[16rem_minmax(0,1fr)_20rem]">
            <aside class="admin-card self-start xl:sticky xl:top-24">
                <a class="text-link mb-4 gap-2" href="{{ route('admin.settings.edit') }}">
                    <x-admin.icon name="settings" class="size-4 shrink-0" />
                    {{ __('Settings overview') }}
                </a>
                <nav class="grid gap-4" aria-label="{{ __('Settings categories') }}">
                    @foreach ($navigationGroups as $group)
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __($group['label']) }}</p>
                            <div class="mt-2 grid gap-1">
                                @foreach ($group['categories'] as $slug => $item)
                                    <a
                                        @class(['mobile-link min-h-10 py-2 text-sm', 'bg-action-50 text-action-800' => $slug === $category])
                                        href="{{ route('admin.settings.show', ['category' => $slug]) }}"
                                        data-settings-icon="{{ $item['icon'] }}"
                                        @if ($slug === $category) aria-current="page" @endif
                                    >
                                        <x-admin.icon :name="$item['icon']" class="size-4 shrink-0 text-action-700" />
                                        <span>{{ __($item['label']) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <a class="mobile-link min-h-10 py-2 text-sm" href="{{ route('admin.settings.history') }}" data-settings-icon="history">
                        <x-admin.icon name="history" class="size-4 shrink-0 text-action-700" />
                        <span>{{ __('Configuration history') }}</span>
                    </a>
                    <a class="mobile-link min-h-10 py-2 text-sm" href="{{ route('admin.settings.diagnostics') }}" data-settings-icon="diagnostics">
                        <x-admin.icon name="diagnostics" class="size-4 shrink-0 text-action-700" />
                        <span>{{ __('Settings diagnostics') }}</span>
                    </a>
                </nav>
            </aside>

            <main>
                <form
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ route('admin.settings.update', ['category' => $category]) }}"
                    data-prevent-duplicate
                    x-data="settingsForm"
                    x-on:input="markChanged"
                    x-on:change="markChanged"
                >
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="settings_version" value="{{ $settingsVersion }}">

                    <div class="admin-panel grid gap-10">
                        @foreach ($groupedDefinitions as $group => $definitionGroup)
                            @php
                                $translatedGroup = __((string) $group);
                                $groupTitle = is_string($translatedGroup)
                                    ? $translatedGroup
                                    : (string) $group;
                            @endphp
                            <x-admin.section
                                :title="$groupTitle"
                                :description="__('Save applies only to this settings category. Environment-managed controls are read-only.')"
                                class="first:border-t-0 first:pt-0"
                            >
                                <div class="grid gap-5 lg:grid-cols-2">
                                    @foreach ($definitionGroup as $key => $definition)
                                        @php
                                            $input = \App\Support\SettingCatalog::inputName($key);
                                            $status = $statuses[$key];
                                            $storedValue = old(
                                                $input,
                                                $definition['type'] === 'media_reference'
                                                    ? $settings->mediaReference($key)
                                                    : $settings->get($key),
                                            );
                                            $inputType = $definition['input'] ?? match ($definition['type']) {
                                                'boolean' => 'checkbox',
                                                'email' => 'email',
                                                'url' => 'url',
                                                'phone' => 'tel',
                                                'integer', 'duration' => 'number',
                                                'color' => 'color',
                                                'text' => 'textarea',
                                                default => 'text',
                                            };
                                        @endphp
                                        <div id="{{ $input }}" class="rounded-xl border border-edge bg-white p-4">
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <label class="form-label mb-1" for="{{ $input }}_field">{{ __($definition['label']) }}</label>
                                                    <p class="text-sm leading-6 text-muted">{{ __($definition['description']) }}</p>
                                                </div>
                                                <div class="flex flex-wrap gap-2 text-xs font-bold">
                                                    @foreach (($definition['effects'] ?? ['immediate']) as $effect)
                                                        <span class="rounded-full bg-action-50 px-2.5 py-1 text-action-800">{{ __(str_replace('_', ' ', ucfirst($effect))) }}</span>
                                                    @endforeach
                                                    @if ($status['environment_managed'])
                                                        <span class="rounded-full bg-warning-bg px-2.5 py-1 text-brand-900">{{ __('Managed by environment') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                @if (! $status['editable'])
                                                    <input class="form-input bg-quiet" id="{{ $input }}_field" value="{{ __($status['effective']) }}" disabled>
                                                    <p class="form-help">{{ __('Stored default') }}: {{ __($status['stored']) }}</p>
                                                    @if ($status['warning'])<p class="form-help text-state-warning">{{ __($status['warning']) }}</p>@endif
                                                @elseif ($definition['type'] === 'boolean')
                                                    <input type="hidden" name="{{ $input }}" value="0">
                                                    <label class="flex min-h-11 items-center gap-3 text-sm" for="{{ $input }}_field">
                                                        <input
                                                            class="size-5 rounded border-slate-400 text-action-500 focus:ring-knowledge-600"
                                                            id="{{ $input }}_field"
                                                            type="checkbox"
                                                            name="{{ $input }}"
                                                            value="1"
                                                            @checked(old($input, $settings->get($key)))
                                                        >
                                                        <span class="text-muted">{{ __('Enabled') }}</span>
                                                    </label>
                                                @elseif ($inputType === 'select')
                                                    <select class="form-input" id="{{ $input }}_field" name="{{ $input }}" @if ($errors->has($input)) aria-invalid="true" @endif>
                                                        @foreach (($definition['options'] ?? []) as $optionValue => $optionLabel)
                                                            <option value="{{ $optionValue }}" @selected((string) $storedValue === (string) $optionValue)>{{ __($optionLabel) }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif ($inputType === 'textarea')
                                                    <textarea class="form-input min-h-28" id="{{ $input }}_field" name="{{ $input }}" @if ($errors->has($input)) aria-invalid="true" @endif>{{ $storedValue }}</textarea>
                                                @elseif ($inputType === 'color')
                                                    <div class="flex items-center gap-3">
                                                        <input class="form-input h-12 w-20 shrink-0 p-1" id="{{ $input }}_field" name="{{ $input }}" type="color" value="{{ $storedValue }}" @if ($errors->has($input)) aria-invalid="true" @endif>
                                                        <span class="font-mono text-sm text-muted">{{ $storedValue }}</span>
                                                    </div>
                                                @elseif ($inputType === 'image')
                                                    <div class="grid gap-3">
                                                        @if (filled($storedValue))
                                                            <div class="rounded-lg border border-edge bg-quiet p-3">
                                                                <p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">{{ __('Current asset') }}</p>
                                                                <div class="mt-3 flex items-center gap-3">
                                                                    <img class="max-h-20 max-w-40 rounded border border-edge bg-white object-contain p-2" src="{{ $storedValue }}" alt="{{ __($definition['label']) }}">
                                                                    <a class="text-link text-sm" href="{{ $storedValue }}" target="_blank" rel="noopener">{{ __('Open current image') }}</a>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        <input
                                                            class="form-input file:me-4 file:rounded-md file:border-0 file:bg-action-50 file:px-4 file:py-2 file:text-sm file:font-bold file:text-action-800"
                                                            id="{{ $input }}_field"
                                                            name="{{ $input }}"
                                                            type="file"
                                                            accept="{{ $definition['accept'] ?? 'image/*' }}"
                                                            @if ($errors->has($input)) aria-invalid="true" @endif
                                                        >
                                                        <p class="form-help">{{ __('Choose an image from your computer. Leave empty to keep the current asset.') }}</p>
                                                    </div>
                                                @else
                                                    <input class="form-input" id="{{ $input }}_field" name="{{ $input }}" type="{{ $inputType }}" value="{{ $storedValue }}" @if ($errors->has($input)) aria-invalid="true" @endif>
                                                @endif
                                                <x-input-error class="mt-2" :messages="$errors->get($input)" />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </x-admin.section>
                        @endforeach

                        <x-admin.section :title="__('Change control')" :description="__('Use a clear reason for high-risk or operationally significant changes.')">
                            <label class="form-label" for="change_reason">{{ __('Change reason') }} @if ($requiresReason)<span class="form-required">({{ __('required') }})</span>@endif</label>
                            <textarea class="form-input min-h-28" id="change_reason" name="change_reason" @if ($requiresReason) required @endif>{{ old('change_reason') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('change_reason')" />
                        </x-admin.section>
                    </div>

                    <div class="sticky bottom-0 z-20 mt-6 rounded-xl border border-edge bg-white/95 p-4 shadow-editorial backdrop-blur">
                        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                            <div class="text-sm">
                                <p class="font-bold text-brand-950" x-show="dirty" x-cloak>{{ __('Unsaved changes') }}</p>
                                <p class="text-muted">
                                    @if ($requiresRecentMfa)
                                        {{ __('Saving this category requires recent MFA.') }}
                                    @else
                                        {{ __('Changes apply after the server confirms the save.') }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <a class="button-secondary" href="{{ route('admin.settings.show', ['category' => $category]) }}">{{ __('Discard changes') }}</a>
                                <button class="button-primary" type="submit">{{ __('Save category') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </main>

            <aside class="grid gap-6 self-start xl:sticky xl:top-24">
                <section class="admin-card">
                    <h2 class="admin-card-title">{{ __('Category status') }}</h2>
                    <div class="mt-4 grid gap-3 text-sm">
                        <p class="status-information">{{ trans_choice(':count control in this category|:count controls in this category', count($definitions), ['count' => count($definitions)]) }}</p>
                        @if ($requiresRecentMfa)
                            <p class="status-warning">{{ __('Sensitive changes require recent MFA and are audited with reason text.') }}</p>
                        @endif
                        <a class="text-link" href="{{ route('admin.settings.history', ['category' => $category]) }}">{{ __('View category history') }}</a>
                    </div>
                </section>

                @if ($category === 'branding')
                    <section class="admin-card">
                        <h2 class="admin-card-title">{{ __('Brand preview') }}</h2>
                        <div class="mt-4 rounded-xl border border-edge p-4">
                            <div class="flex items-center gap-3">
                                <span class="brand-mark" aria-hidden="true">I</span>
                                <div>
                                    <p class="font-extrabold text-brand-900">{{ $settings->effective('site.short_name') }}</p>
                                    <p class="text-xs uppercase tracking-[0.16em] text-muted">{{ $settings->effective('branding.footer_tagline') }}</p>
                                </div>
                            </div>
                            <button type="button" class="button-primary mt-4">{{ __('Primary action') }}</button>
                        </div>
                    </section>
                @endif

                @if ($category === 'notifications')
                    <section class="admin-card">
                        <h2 class="admin-card-title">{{ __('Notification matrix') }}</h2>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-edge text-xs uppercase tracking-wide text-muted">
                                        <th class="py-2 pe-3">{{ __('Event') }}</th>
                                        <th class="py-2 pe-3">{{ __('Database') }}</th>
                                        <th class="py-2 pe-3">{{ __('Email') }}</th>
                                        <th class="py-2 pe-3">{{ __('Mandatory') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ([
                                        __('Security alerts') => ['notifications.database_enabled', 'notifications.email_enabled', true],
                                        __('Engagement submissions') => ['notifications.database_enabled', 'notifications.engagement_submissions', false],
                                        __('Applications') => ['notifications.database_enabled', 'notifications.application_submissions', false],
                                    ] as $event => [$databaseKey, $emailKey, $mandatory])
                                        <tr class="border-b border-edge last:border-b-0">
                                            <td class="py-2 pe-3 font-bold text-brand-950">{{ $event }}</td>
                                            <td class="py-2 pe-3">{{ $settings->effective($databaseKey) ? __('Enabled') : __('Disabled') }}</td>
                                            <td class="py-2 pe-3">{{ $settings->effective($emailKey) ? __('Enabled') : __('Disabled') }}</td>
                                            <td class="py-2 pe-3">{{ $mandatory ? __('Yes') : __('No') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <section class="admin-card">
                    <h2 class="admin-card-title">{{ __('Restore defaults') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-muted">{{ __('This restores only this category. Environment-managed values are not changed.') }}</p>
                    <form method="POST" action="{{ route('admin.settings.reset', ['category' => $category]) }}" class="mt-4 grid gap-3" data-prevent-duplicate>
                        @csrf
                        <input type="hidden" name="category" value="{{ $category }}">
                        <input type="hidden" name="settings_version" value="{{ $settingsVersion }}">
                        <label class="form-label" for="confirm_category">{{ __('Type the category key to confirm') }}</label>
                        <input class="form-input" id="confirm_category" name="confirm_category" autocomplete="off">
                        <label class="form-label" for="reset_change_reason">{{ __('Change reason') }}</label>
                        <textarea class="form-input min-h-24" id="reset_change_reason" name="change_reason" @if ($requiresReason) required @endif></textarea>
                        <button class="button-secondary" type="submit">{{ __('Restore category defaults') }}</button>
                    </form>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
