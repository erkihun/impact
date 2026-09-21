<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Settings Center')"
            :title="__('Configuration history')"
            :description="__('Read-only audit history for settings updates and category resets. Sensitive values are displayed only as safe status text.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <div class="grid gap-6 xl:grid-cols-[16rem_minmax(0,1fr)]">
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
                                    <a class="mobile-link min-h-10 py-2 text-sm" href="{{ route('admin.settings.show', ['category' => $slug]) }}" data-settings-icon="{{ $item['icon'] }}">
                                        <x-admin.icon :name="$item['icon']" class="size-4 shrink-0 text-action-700" />
                                        <span>{{ __($item['label']) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <a class="mobile-link min-h-10 bg-action-50 py-2 text-sm text-action-800" href="{{ route('admin.settings.history') }}" data-settings-icon="history" aria-current="page">
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
                    <form method="GET" action="{{ route('admin.settings.history') }}" class="grid gap-4 lg:grid-cols-3">
                        <div>
                            <label class="form-label" for="category">{{ __('Category') }}</label>
                            <select class="form-input" id="category" name="category">
                                <option value="">{{ __('All categories') }}</option>
                                @foreach ($categories as $slug => $category)
                                    <option value="{{ $slug }}" @selected($filters['category'] === $slug)>{{ __($category['label']) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="setting">{{ __('Setting key') }}</label>
                            <input class="form-input" id="setting" name="setting" value="{{ $filters['setting'] }}">
                        </div>
                        <div>
                            <label class="form-label" for="actor">{{ __('Actor') }}</label>
                            <input class="form-input" id="actor" name="actor" value="{{ $filters['actor'] }}">
                        </div>
                        <div class="lg:col-span-3">
                            <button class="button-primary" type="submit">{{ __('Filter history') }}</button>
                        </div>
                    </form>
                </section>

                <section class="admin-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-edge text-xs uppercase tracking-wide text-muted">
                                    <th class="py-3 pe-4">{{ __('Setting') }}</th>
                                    <th class="py-3 pe-4">{{ __('Category') }}</th>
                                    <th class="py-3 pe-4">{{ __('Previous') }}</th>
                                    <th class="py-3 pe-4">{{ __('New') }}</th>
                                    <th class="py-3 pe-4">{{ __('Actor') }}</th>
                                    <th class="py-3 pe-4">{{ __('Reason') }}</th>
                                    <th class="py-3 pe-4">{{ __('Time') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($events as $event)
                                    <tr class="border-b border-edge last:border-b-0">
                                        <td class="py-3 pe-4 font-bold text-brand-950">{{ __(data_get($event->metadata, 'label', 'Settings updated')) }}</td>
                                        <td class="py-3 pe-4">{{ __(data_get($categories, data_get($event->metadata, 'category').'.label', data_get($event->metadata, 'category', 'Unknown'))) }}</td>
                                        <td class="py-3 pe-4">{{ __(data_get($event->metadata, 'previous_value', 'Not available')) }}</td>
                                        <td class="py-3 pe-4">{{ __(data_get($event->metadata, 'new_value', 'Not available')) }}</td>
                                        <td class="py-3 pe-4">{{ optional($event->actor)->name ?? __('System') }}</td>
                                        <td class="py-3 pe-4">{{ data_get($event->metadata, 'change_reason') ?: __('Not recorded') }}</td>
                                        <td class="py-3 pe-4">{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-6 text-muted" colspan="7">{{ __('No settings history matches the filters.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $events->links() }}</div>
                </section>
            </main>
        </div>
    </div>
</x-app-layout>
