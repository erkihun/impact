<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Security and accountability')"
            :title="__('Audit events')"
            :description="__('An append-only record of who changed what, and when. Entries cannot be edited or removed.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <x-admin.filter-bar
            :legend="__('Filter audit events')"
            :has-active-filters="request()->filled('action') || request()->filled('from') || request()->filled('to')"
            :clear-url="route('admin.audit.index')"
        >
            <div>
                <label class="form-label" for="audit-action-filter">{{ __('Action') }}</label>
                <input class="form-input" id="audit-action-filter" name="action" value="{{ request('action') }}" type="search" placeholder="{{ __('For example: identity.') }}">
                <p class="form-help">{{ __('Matches from the start, so “identity.” returns every identity event.') }}</p>
            </div>
            <div>
                <label class="form-label" for="audit-from-filter">{{ __('From date') }}</label>
                <input class="form-input" id="audit-from-filter" name="from" value="{{ request('from') }}" type="date">
            </div>
            <div>
                <label class="form-label" for="audit-to-filter">{{ __('To date') }}</label>
                <input class="form-input" id="audit-to-filter" name="to" value="{{ request('to') }}" type="date">
            </div>
        </x-admin.filter-bar>

        @if ($errors->any())
            <div class="error-summary mt-6" data-error-summary tabindex="-1" role="alert">
                <p class="font-bold">{{ __('The filters could not be applied.') }}</p>
                <ul class="mt-2 grid gap-1">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-admin.result-summary :paginator="$events" :context="__('Append-only history')" />

        @if ($events->total() > 0)
            <div class="mt-5">
                <x-admin.record-list
                    :label="__('Audit history')"
                    :columns="[
                        ['label' => __('When')],
                        ['label' => __('Action')],
                        ['label' => __('Actor')],
                        ['label' => __('Correlation')],
                    ]"
                >
                    @foreach ($events as $event)
                        <tr>
                            <x-admin.cell :label="__('When')">
                                @if ($event->created_at)
                                    <time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->isoFormat('LLL') }}</time>
                                @else
                                    {{ __('Not recorded') }}
                                @endif
                            </x-admin.cell>
                            <x-admin.cell :label="__('Action')" primary>{{ $event->action }}</x-admin.cell>
                            <x-admin.cell :label="__('Actor')">{{ $event->actor?->name ?? __('System') }}</x-admin.cell>
                            <x-admin.cell :label="__('Correlation')" numeric>{{ $event->correlation_id }}</x-admin.cell>
                        </tr>
                    @endforeach
                </x-admin.record-list>
            </div>
            <div class="mt-6">{{ $events->links() }}</div>
        @else
            <div class="mt-5">
                <x-ui.empty-state
                    :title="__('No audit events match these filters.')"
                    :description="__('Widen the date range or clear the action filter. Events are recorded automatically as people work in the platform.')"
                >
                    @if (request()->filled('action') || request()->filled('from') || request()->filled('to'))
                        <a class="button-primary" href="{{ route('admin.audit.index') }}">{{ __('Clear filters') }}</a>
                    @endif
                </x-ui.empty-state>
            </div>
        @endif
    </div>
</x-app-layout>
