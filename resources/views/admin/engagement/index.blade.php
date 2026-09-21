<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Client engagement')"
            :title="__('Engagement queue')"
            :description="__('Triage consultation, proposal and contact requests, then assign them to the responsible team.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <x-admin.filter-bar
            :legend="__('Filter engagement records')"
            :has-active-filters="request()->filled('status') || request()->filled('type')"
            :clear-url="route('admin.engagement.index')"
        >
            <div>
                <label class="form-label" for="engagement-status-filter">{{ __('Status') }}</label>
                <select class="form-input" id="engagement-status-filter" name="status">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (\App\Enums\SubmissionStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ __(str($status->value)->headline()->toString()) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="engagement-type-filter">{{ __('Request type') }}</label>
                <select class="form-input" id="engagement-type-filter" name="type">
                    <option value="">{{ __('All types') }}</option>
                    @foreach (\App\Enums\SubmissionType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ __(str($type->value)->headline()->toString()) }}</option>
                    @endforeach
                </select>
            </div>
        </x-admin.filter-bar>

        <x-admin.result-summary :paginator="$submissions" :context="__('Current engagement register')" />

        @if ($submissions->total() > 0)
            <div class="mt-5">
                <x-admin.record-list
                    :label="__('Engagement records')"
                    :columns="[
                        ['label' => __('Reference')],
                        ['label' => __('Organization')],
                        ['label' => __('Request type')],
                        ['label' => __('Status')],
                        ['label' => __('Assigned')],
                        ['label' => __('Received')],
                        ['label' => __('Actions'), 'srOnly' => true],
                    ]"
                >
                    @foreach ($submissions as $submission)
                        <tr>
                            <x-admin.cell :label="__('Reference')" numeric>
                                <a class="font-bold text-action-700 hover:underline" href="{{ route('admin.engagement.show', $submission) }}">{{ $submission->reference_no }}</a>
                            </x-admin.cell>
                            <x-admin.cell :label="__('Organization')" primary>{{ $submission->organization_name ?: $submission->contact_name }}</x-admin.cell>
                            <x-admin.cell :label="__('Request type')">{{ __(str($submission->type->value)->headline()->toString()) }}</x-admin.cell>
                            <x-admin.cell :label="__('Status')"><x-ui.status-badge :status="$submission->status" /></x-admin.cell>
                            <x-admin.cell :label="__('Assigned')">{{ $submission->assignee?->name ?: __('Unassigned') }}</x-admin.cell>
                            <x-admin.cell :label="__('Received')">
                                @if ($submission->submitted_at)
                                    <time datetime="{{ $submission->submitted_at->toIso8601String() }}">{{ $submission->submitted_at->isoFormat('LLL') }}</time>
                                @else
                                    {{ __('Not recorded') }}
                                @endif
                            </x-admin.cell>
                            <x-admin.row-actions :label="__('Actions')">
                                <a class="button-tertiary" href="{{ route('admin.engagement.show', $submission) }}">{{ __('Open record') }} <span aria-hidden="true">→</span></a>
                            </x-admin.row-actions>
                        </tr>
                    @endforeach
                </x-admin.record-list>
            </div>
            <div class="mt-6">{{ $submissions->links() }}</div>
        @else
            <div class="mt-5">
                <x-ui.empty-state
                    :title="__('No engagement records match these filters.')"
                    :description="__('Clear the filters to see the full queue. New consultation, proposal and contact requests appear here as soon as they are submitted.')"
                >
                    @if (request()->filled('status') || request()->filled('type'))
                        <a class="button-primary" href="{{ route('admin.engagement.index') }}">{{ __('Clear filters') }}</a>
                    @endif
                </x-ui.empty-state>
            </div>
        @endif
    </div>
</x-app-layout>
