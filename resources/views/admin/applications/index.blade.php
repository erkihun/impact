<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Recruitment')"
            :title="__('Applications')"
            :description="__('Review candidate applications received through published vacancies.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <x-admin.filter-bar
            :legend="__('Filter applications')"
            :has-active-filters="request()->filled('status')"
            :clear-url="route('admin.applications.index')"
        >
            <div>
                <label class="form-label" for="application-status-filter">{{ __('Application status') }}</label>
                <select class="form-input" id="application-status-filter" name="status">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (\App\Enums\ApplicationStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ __(str($status->value)->headline()->toString()) }}</option>
                    @endforeach
                </select>
            </div>
        </x-admin.filter-bar>

        <x-admin.result-summary :paginator="$applications" :context="__('Candidate register')" />

        @if ($applications->total() > 0)
            <div class="mt-5">
                <x-admin.record-list
                    :label="__('Applications')"
                    :columns="[
                        ['label' => __('Reference')],
                        ['label' => __('Applicant')],
                        ['label' => __('Vacancy')],
                        ['label' => __('Status')],
                        ['label' => __('Submitted')],
                        ['label' => __('Actions'), 'srOnly' => true],
                    ]"
                >
                    @foreach ($applications as $application)
                        <tr>
                            <x-admin.cell :label="__('Reference')" numeric>
                                <a class="font-bold text-action-700 hover:underline" href="{{ route('admin.applications.show', $application) }}">{{ $application->reference_no }}</a>
                            </x-admin.cell>
                            <x-admin.cell :label="__('Applicant')" primary>{{ $application->applicant_name }}</x-admin.cell>
                            <x-admin.cell :label="__('Vacancy')">{{ $application->vacancy->title }}</x-admin.cell>
                            <x-admin.cell :label="__('Status')"><x-ui.status-badge :status="$application->status" /></x-admin.cell>
                            <x-admin.cell :label="__('Submitted')">
                                <time datetime="{{ $application->submitted_at->toIso8601String() }}">{{ $application->submitted_at->timezone(config('app.timezone'))->isoFormat('LLL') }}</time>
                            </x-admin.cell>
                            <x-admin.row-actions :label="__('Actions')">
                                <a class="button-tertiary" href="{{ route('admin.applications.show', $application) }}">{{ __('Open record') }} <span aria-hidden="true">→</span></a>
                            </x-admin.row-actions>
                        </tr>
                    @endforeach
                </x-admin.record-list>
            </div>
            <div class="mt-6">{{ $applications->links() }}</div>
        @else
            <div class="mt-5">
                <x-ui.empty-state
                    :title="__('No applications match this filter.')"
                    :description="__('Clear the filter to see every application. New submissions appear here as candidates apply to published vacancies.')"
                >
                    @if (request()->filled('status'))
                        <a class="button-primary" href="{{ route('admin.applications.index') }}">{{ __('Clear filters') }}</a>
                    @endif
                </x-ui.empty-state>
            </div>
        @endif
    </div>
</x-app-layout>
