<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Platform configuration')"
            :title="__('Settings Center')"
            :description="__('Search, review and manage application-controlled settings without exposing environment secrets.')"
        />
    </x-slot>

    <div class="admin-workspace">
        @if (session('status'))
            <div class="status-success mb-6" role="status">{{ session('status') }}</div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[16rem_minmax(0,1fr)_20rem]">
            <aside class="admin-card self-start xl:sticky xl:top-24">
                <h2 class="text-sm font-black uppercase tracking-[0.14em] text-brand-900">{{ __('Settings navigation') }}</h2>
                <nav class="mt-4 grid gap-4" aria-label="{{ __('Settings categories') }}">
                    @foreach ($navigationGroups as $group)
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __($group['label']) }}</p>
                            <div class="mt-2 grid gap-1">
                                @foreach ($group['categories'] as $slug => $item)
                                    <a class="mobile-link min-h-10 py-2 text-sm" href="{{ route('admin.settings.show', ['category' => $slug]) }}" data-settings-icon="{{ $item['icon'] }}">
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

            <main class="grid gap-6">
                <section class="admin-card">
                    <form method="GET" action="{{ route('admin.settings.edit') }}" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                        <div>
                            <label class="form-label" for="settings-search">{{ __('Search settings') }}</label>
                            <input class="form-input" id="settings-search" name="q" value="{{ $search }}" placeholder="{{ __('Search by label, description, category, or key') }}">
                        </div>
                        <div class="flex items-end">
                            <button class="button-primary w-full" type="submit">{{ __('Search') }}</button>
                        </div>
                    </form>

                    @if ($search !== '')
                        <div class="mt-5 border-t border-edge pt-5">
                            <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Search results') }}</h2>
                            <div class="mt-3 grid gap-3">
                                @forelse ($searchResults as $result)
                                    <a class="admin-card admin-card-interactive block" href="{{ route('admin.settings.show', ['category' => $result['category']]) }}#{{ \App\Support\SettingCatalog::inputName($result['key']) }}">
                                        <span class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.14em] text-action-700">
                                            <x-admin.icon :name="$result['category_icon']" class="size-4 shrink-0" />
                                            {{ __($result['category_label']) }}
                                        </span>
                                        <span class="mt-1 block font-bold text-brand-950">{{ __($result['label']) }}</span>
                                        <span class="mt-1 block text-sm leading-6 text-muted">{{ __($result['description']) }}</span>
                                        <span class="mt-2 block text-xs text-muted">{{ __('Effective value') }}: {{ __($result['status']['effective']) }}</span>
                                    </a>
                                @empty
                                    <p class="text-sm text-muted">{{ __('No settings matched your search.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </section>

                <section class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3" aria-label="{{ __('Settings categories') }}">
                    @foreach ($categories as $card)
                        <a class="admin-card admin-card-interactive block" href="{{ route('admin.settings.show', ['category' => $card['category']]) }}" data-settings-icon="{{ $card['icon'] }}">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-action-50 text-action-700">
                                <x-admin.icon :name="$card['icon']" class="size-5" />
                            </span>
                            <span class="mt-4 block text-xs font-black uppercase tracking-[0.14em] text-action-700">{{ __($card['group']) }}</span>
                            <span class="mt-2 block font-editorial text-lg font-bold text-brand-950">{{ __($card['label']) }}</span>
                            <span class="mt-2 block text-sm leading-6 text-muted">{{ __($card['description']) }}</span>
                            <span class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
                                <span class="rounded-full bg-action-50 px-3 py-1 text-action-800">{{ trans_choice(':count control|:count controls', $card['controls'], ['count' => $card['controls']]) }}</span>
                                @if ($card['incomplete'] > 0)
                                    <span class="rounded-full bg-warning-bg px-3 py-1 text-brand-900">{{ trans_choice(':count incomplete|:count incomplete', $card['incomplete'], ['count' => $card['incomplete']]) }}</span>
                                @else
                                    <span class="rounded-full bg-success-bg px-3 py-1 text-state-success">{{ __('Configured') }}</span>
                                @endif
                                @if ($card['requires_recent_mfa'])
                                    <span class="rounded-full bg-warning-bg px-3 py-1 text-brand-900">{{ __('Recent MFA') }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </section>
            </main>

            <aside class="grid gap-6 self-start xl:sticky xl:top-24">
                <section class="admin-card">
                    <h2 class="admin-card-title">{{ __('Environment') }}</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">{{ __('Name') }}</dt>
                            <dd class="font-bold text-brand-950">{{ $environment['app_environment'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">{{ __('Cache') }}</dt>
                            <dd class="font-bold text-brand-950">{{ $environment['cache_driver'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">{{ __('Queue') }}</dt>
                            <dd class="font-bold text-brand-950">{{ $environment['queue_driver'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">{{ __('Email') }}</dt>
                            <dd class="font-bold text-brand-950">{{ $environment['email_provider'] }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="admin-card">
                    <h2 class="admin-card-title">{{ __('Configuration health') }}</h2>
                    <div class="mt-4 grid gap-3 text-sm">
                        <p class="status-information">{{ __('Environment-managed values are read-only and marked on each category page.') }}</p>
                        <p class="status-information">{{ __('Sensitive categories require recent MFA and a change reason.') }}</p>
                        <p class="status-information">{{ __('Settings cache is cleared after committed changes.') }}</p>
                    </div>
                </section>

                <section class="admin-card">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="admin-card-title">{{ __('Recent changes') }}</h2>
                        <a class="text-link min-h-0 py-0" href="{{ route('admin.settings.history') }}">{{ __('History') }}</a>
                    </div>
                    <div class="mt-4 grid gap-3 text-sm">
                        @forelse ($recentChanges as $event)
                            <div class="border-t border-edge pt-3 first:border-t-0 first:pt-0">
                                <p class="font-bold text-brand-950">{{ __(data_get($event->metadata, 'label', 'Settings updated')) }}</p>
                                <p class="text-xs text-muted">{{ optional($event->actor)->name ?? __('System') }} · {{ $event->created_at?->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="text-muted">{{ __('No settings changes recorded yet.') }}</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
