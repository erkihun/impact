<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Settings Center')"
            :title="__('Settings diagnostics')"
            :description="__('Read-only verification of registry, stored values, effective sources, consumers, and localization.')"
        />
    </x-slot>

    <div class="admin-workspace grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a class="text-link gap-2" href="{{ route('admin.settings.edit') }}">
                <x-admin.icon name="settings" class="size-4 shrink-0" />
                {{ __('Settings overview') }}
            </a>
            <form method="POST" action="{{ route('admin.settings.diagnostics.run') }}" data-prevent-duplicate>
                @csrf
                <button class="button-primary" type="submit">{{ __('Run verification') }}</button>
            </form>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="{{ __('Verification summary') }}">
            @foreach ($report['summary'] as $metric => $count)
                <div class="admin-kpi">
                    <p class="admin-kpi-label">{{ __(str_replace('_', ' ', ucfirst($metric))) }}</p>
                    <p class="admin-kpi-value">{{ $count }}</p>
                </div>
            @endforeach
        </section>

        <section class="admin-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="admin-card-title">{{ __('Verification findings') }}</h2>
                    <p class="admin-card-meta">{{ __('Generated at :time', ['time' => $report['generated_at']]) }}</p>
                </div>
            </div>

            @if ($report['issues'] === [])
                <div class="status-success mt-5">{{ __('No verification defects were detected.') }}</div>
            @else
                <div class="mt-5 overflow-x-auto">
                    <table>
                        <thead><tr><th>{{ __('Severity') }}</th><th>{{ __('Code') }}</th><th>{{ __('Setting') }}</th><th>{{ __('Finding') }}</th></tr></thead>
                        <tbody>
                            @foreach ($report['issues'] as $issue)
                                <tr>
                                    <td><span @class(['status-badge', 'status-badge-danger' => $issue['severity'] === 'error', 'status-badge-warning' => $issue['severity'] !== 'error'])>{{ __($issue['severity']) }}</span></td>
                                    <td class="font-mono text-xs">{{ $issue['code'] }}</td>
                                    <td class="font-mono text-xs">{{ $issue['key'] ?? '—' }}</td>
                                    <td>{{ __($issue['message']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="admin-card">
            <h2 class="admin-card-title">{{ __('Consumption matrix') }}</h2>
            <div class="mt-5 overflow-x-auto">
                <table>
                    <thead><tr><th>{{ __('Setting') }}</th><th>{{ __('Source') }}</th><th>{{ __('Consumer') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @foreach ($report['settings'] as $setting)
                            <tr>
                                <td><span class="font-mono text-xs">{{ $setting['key'] }}</span><span class="mt-1 block text-xs text-muted">{{ __($setting['behavior']) }}</span></td>
                                <td>{{ __($setting['source']) }}</td>
                                <td>{{ $setting['consumer'] ?? __('Not editable') }}<span class="mt-1 block font-mono text-xs text-muted">{{ $setting['consumer_file'] }}</span></td>
                                <td>{{ __($setting['status']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
