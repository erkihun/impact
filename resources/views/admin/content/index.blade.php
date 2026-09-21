<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Editorial operations')"
            :title="__('Content workspace')"
            :description="__('Review publication state, ownership and language coverage from one operational record.')"
        >
            <x-slot name="action">
                <a class="button-primary" href="{{ route('admin.content.create') }}">{{ __('Create content') }} <span aria-hidden="true">+</span></a>
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_16rem]">
            <div>
                <x-admin.filter-bar
                    :legend="__('Filter content records')"
                    :has-active-filters="request()->filled('status') || request()->filled('type')"
                    :clear-url="route('admin.content.index')"
                >
                    <div>
                        <label class="form-label" for="content-status-filter">{{ __('Publication state') }}</label>
                        <select class="form-input" id="content-status-filter" name="status">
                            <option value="">{{ __('All states') }}</option>
                            @foreach (\App\Enums\ContentWorkflowState::cases() as $state)<option value="{{ $state->value }}" @selected(request('status') === $state->value)>{{ str($state->value)->headline() }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="content-type-filter">{{ __('Content architecture') }}</label>
                        <select class="form-input" id="content-type-filter" name="type">
                            <option value="">{{ __('All types') }}</option>
                            @foreach (\App\Enums\ContentType::cases() as $type)<option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ str($type->value)->headline() }}</option>@endforeach
                        </select>
                    </div>
                </x-admin.filter-bar>

                <x-admin.result-summary :paginator="$items" :context="__('Current editorial register')" />

                <div class="mt-5 grid gap-3">
                    @forelse ($items as $item)
                        <x-admin.content-summary-card
                            :title="$item->currentVersion?->title ?? __('Untitled content')"
                            :type="__(str($item->type->value)->headline()->toString())"
                            :locale="strtoupper((string) $item->currentVersion?->locale)"
                            :owner="$item->owner?->name ?? __('Unassigned')"
                            :status="$item->status"
                            :href="route('admin.content.show', $item)"
                        />
                    @empty
                        <x-ui.empty-state :title="__('No content matches these filters.')" :description="__('Clear the filters or create the first record for this editorial view.')">
                            <a class="button-primary" href="{{ route('admin.content.create') }}">{{ __('Create content') }}</a>
                        </x-ui.empty-state>
                    @endforelse
                </div>
                <div class="mt-6">{{ $items->links() }}</div>
            </div>

            <aside class="admin-attention-rail h-fit lg:sticky lg:top-24">
                <p class="eyebrow">{{ __('Workspace lens') }}</p>
                <h2 class="mt-4 font-editorial text-xl font-bold text-brand-950">{{ __('Publication control, at a glance') }}</h2>
                <p class="mt-3 text-sm leading-7 text-muted">{{ __('Use state and type together to narrow the register. Open a record to inspect revisions, preview output or move it through workflow.') }}</p>
                <dl class="mt-6 divide-y divide-edge text-sm">
                    <div class="py-3"><dt class="font-bold text-brand-950">{{ __('Visible records') }}</dt><dd class="mt-1 font-mono text-2xl font-bold text-action-700">{{ $items->count() }}</dd></div>
                    <div class="py-3"><dt class="font-bold text-brand-950">{{ __('Register total') }}</dt><dd class="mt-1 font-mono text-2xl font-bold text-action-700">{{ $items->total() }}</dd></div>
                </dl>
            </aside>
        </section>
    </div>
</x-app-layout>
