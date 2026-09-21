@php
    $groups = [
        'site' => ['title' => __('Organization identity'), 'description' => __('How the organization is named and reached. These values appear on the public website.')],
        'branding' => ['title' => __('Branding'), 'description' => __('Core visual identity values for public and administrative surfaces.')],
        'appearance' => ['title' => __('Appearance'), 'description' => __('Display preferences for public pages and the CMS workspace.')],
        'security' => ['title' => __('Security'), 'description' => __('Security contacts and operator-facing policy records. Authentication enforcement remains handled by the application security layer.')],
        'notifications' => ['title' => __('Notifications'), 'description' => __('Recipient and delivery preferences for system-generated messages.')],
        'email' => ['title' => __('Email'), 'description' => __('Sender identity used by outgoing mail templates.')],
        'social' => ['title' => __('Social presence'), 'description' => __('External profiles linked from the public site.')],
        'seo' => ['title' => __('Search and sharing'), 'description' => __('Defaults used when a page does not define its own title or description.')],
        'localization' => ['title' => __('Language'), 'description' => __('Behaviour when content is not available in the requested language.')],
        'analytics' => ['title' => __('Analytics'), 'description' => __('Measurement is applied only where a visitor has given consent.')],
        'privacy' => ['title' => __('Privacy'), 'description' => __('Raising the notice version asks every visitor for their privacy choices again.')],
        'operations' => ['title' => __('Operations'), 'description' => __('Support and maintenance messaging for day-to-day operations.')],
    ];

    $grouped = [];
    foreach ($definitions as $key => $definition) {
        $prefix = str_contains($key, '.') ? strtok($key, '.') : 'other';
        $grouped[array_key_exists($prefix, $groups) ? $prefix : 'other'][$key] = $definition;
    }

    $grouped = collect($groups)
        ->keys()
        ->filter(fn (string $prefix): bool => array_key_exists($prefix, $grouped))
        ->mapWithKeys(fn (string $prefix): array => [$prefix => $grouped[$prefix]])
        ->merge(array_diff_key($grouped, $groups))
        ->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Platform configuration')"
            :title="__('System settings')"
            :description="__('Values that apply across the whole platform. Changes take effect immediately for new page requests.')"
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

        <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-6xl" data-prevent-duplicate>
            @csrf
            @method('PUT')

            <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($grouped as $prefix => $definitionGroup)
                    <a href="#settings-{{ $prefix }}" class="admin-card admin-card-interactive block">
                        <span class="text-xs font-black uppercase tracking-[0.14em] text-action-700">{{ __('Settings group') }}</span>
                        <span class="mt-2 block font-editorial text-lg font-bold text-brand-950">{{ $groups[$prefix]['title'] ?? __('Other settings') }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ trans_choice(':count control|:count controls', count($definitionGroup), ['count' => count($definitionGroup)]) }}</span>
                    </a>
                @endforeach
            </div>

            <div class="admin-panel grid gap-10">
                @foreach ($grouped as $prefix => $definitionGroup)
                    <x-admin.section
                        :title="$groups[$prefix]['title'] ?? __('Other settings')"
                        :description="$groups[$prefix]['description'] ?? null"
                        id="settings-{{ $prefix }}"
                        class="first:border-t-0 first:pt-0"
                    >
                        <div class="grid gap-5 lg:grid-cols-2">
                            @foreach ($definitionGroup as $key => $definition)
                                @php
                                    $input = \App\Support\SettingCatalog::inputName($key);
                                    $inputValue = old($input, $values[$key] ?? '');
                                    $inputType = $definition['input'] ?? match (true) {
                                        in_array('email:rfc', $definition['rule'], true) => 'email',
                                        in_array('url:http,https', $definition['rule'], true) => 'url',
                                        in_array('date_format:Y-m-d', $definition['rule'], true) => 'date',
                                        $definition['type'] === 'integer' => 'number',
                                        default => 'text',
                                    };
                                @endphp
                                <div>
                                    <label class="form-label" for="{{ $input }}">{{ __($definition['label']) }}</label>
                                    @if ($definition['type'] === 'boolean')
                                        <input type="hidden" name="{{ $input }}" value="0">
                                        <label class="flex min-h-11 items-center gap-3 text-sm" for="{{ $input }}">
                                            <input
                                                class="size-5 rounded border-slate-400 text-action-500 focus:ring-knowledge-600"
                                                id="{{ $input }}"
                                                type="checkbox"
                                                name="{{ $input }}"
                                                value="1"
                                                @checked(old($input, $values[$key] ?? false))
                                            >
                                            <span class="text-muted">{{ __('Enabled') }}</span>
                                        </label>
                                    @elseif ($inputType === 'select')
                                        <select
                                            class="form-input"
                                            id="{{ $input }}"
                                            name="{{ $input }}"
                                            @if ($errors->has($input)) aria-invalid="true" @endif
                                        >
                                            @foreach (($definition['options'] ?? []) as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}" @selected((string) $inputValue === (string) $optionValue)>{{ __($optionLabel) }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($inputType === 'textarea')
                                        <textarea
                                            class="form-input min-h-28"
                                            id="{{ $input }}"
                                            name="{{ $input }}"
                                            @if ($errors->has($input)) aria-invalid="true" @endif
                                        >{{ $inputValue }}</textarea>
                                    @elseif ($inputType === 'color')
                                        <div class="flex items-center gap-3">
                                            <input
                                                class="form-input h-12 w-20 shrink-0 p-1"
                                                id="{{ $input }}"
                                                name="{{ $input }}"
                                                type="color"
                                                value="{{ $inputValue }}"
                                                @if ($errors->has($input)) aria-invalid="true" @endif
                                            >
                                            <span class="font-mono text-sm text-muted">{{ $inputValue }}</span>
                                        </div>
                                    @else
                                        <input
                                            class="form-input"
                                            id="{{ $input }}"
                                            name="{{ $input }}"
                                            type="{{ $inputType }}"
                                            value="{{ $inputValue }}"
                                            @if ($errors->has($input)) aria-invalid="true" @endif
                                        >
                                    @endif
                                    @if (isset($definition['description']))
                                        <p class="form-help">{{ __($definition['description']) }}</p>
                                    @endif
                                    <x-input-error class="mt-2" :messages="$errors->get($input)" />
                                </div>
                            @endforeach
                        </div>
                    </x-admin.section>
                @endforeach
            </div>

            <div class="mt-6">
                <button class="button-primary" type="submit">{{ __('Save settings') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
