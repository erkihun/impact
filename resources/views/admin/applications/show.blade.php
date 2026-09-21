<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Recruitment') . ' · ' . $application->reference_no"
            :title="$application->applicant_name"
            :description="__('Candidate information is restricted. Do not copy it outside this workspace.')"
        >
            <x-slot name="action">
                <a class="button-secondary" href="{{ route('admin.applications.index') }}">{{ __('Back to applications') }}</a>
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        @if (session('status'))
            <div class="status-success mb-6" role="status">{{ session('status') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div class="grid gap-6">
                <section class="admin-panel">
                    <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Application detail') }}</h2>
                    <dl class="mt-5 grid gap-5 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __('Email') }}</dt>
                            <dd class="mt-1 font-semibold text-brand-950">{{ $application->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __('Vacancy') }}</dt>
                            <dd class="mt-1 font-semibold text-brand-950">{{ $application->vacancy->title }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __('Status') }}</dt>
                            <dd class="mt-1"><x-ui.status-badge :status="$application->status" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __('Retained until') }}</dt>
                            <dd class="mt-1 font-semibold text-brand-950">
                                <time datetime="{{ $application->retention_until->toDateString() }}">{{ $application->retention_until->isoFormat('LL') }}</time>
                            </dd>
                        </div>
                    </dl>

                    @if ($application->cover_letter_encrypted)
                        <div class="mt-8 border-t border-slate-200 pt-6">
                            <h3 class="font-bold text-brand-950">{{ __('Cover letter') }}</h3>
                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $application->cover_letter_encrypted }}</p>
                        </div>
                    @endif

                    <div class="mt-8 border-t border-slate-200 pt-6">
                        <h3 class="font-bold text-brand-950">{{ __('Restricted files') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-muted">{{ __('Download links are single-use and expire after five minutes.') }}</p>
                        @if ($application->files->isNotEmpty())
                            <ul class="mt-4 grid gap-2">
                                @foreach ($application->files as $file)
                                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-300 p-3">
                                        <span class="min-w-0 break-words text-sm font-semibold text-brand-950">{{ $file->mediaAsset->original_name }}</span>
                                        <a class="button-tertiary" href="{{ URL::temporarySignedRoute('application-files.download', now()->addMinutes(5), ['file' => $file]) }}">{{ __('Download') }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-4 text-sm text-muted">{{ __('No files were attached to this application.') }}</p>
                        @endif
                    </div>
                </section>

                <section class="admin-panel">
                    <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Status history') }}</h2>
                    @if ($application->history->isNotEmpty())
                        <ol class="workflow-rail mt-5">
                            @foreach ($application->history->sortByDesc('created_at') as $event)
                                <li @if ($loop->first) class="is-current" @endif>
                                    <p class="font-bold text-brand-950">{{ __(str($event->to_status->value)->headline()->toString()) }}</p>
                                    <p class="mt-1 text-sm text-muted">
                                        @if ($event->created_at)
                                            <time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->isoFormat('LLL') }}</time>
                                        @endif
                                        @if ($event->reason) · {{ $event->reason }} @endif
                                    </p>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="mt-4 text-sm text-muted">{{ __('No status changes have been recorded yet.') }}</p>
                    @endif
                </section>
            </div>

            <aside class="admin-panel h-fit lg:sticky lg:top-24">
                <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Change status') }}</h2>
                @if (count($destinations))
                    <form method="POST" action="{{ route('admin.applications.update', $application) }}" class="mt-5 grid gap-4" data-prevent-duplicate>
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="form-label" for="status">{{ __('Move to') }}</label>
                            <select class="form-input" id="status" name="status">
                                @foreach ($destinations as $status)
                                    <option value="{{ $status->value }}">{{ __(str($status->value)->headline()->toString()) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="reason">{{ __('Reason') }}</label>
                            <textarea class="form-input" id="reason" name="reason" rows="4" required aria-describedby="reason-help"></textarea>
                            <p class="form-help" id="reason-help">{{ __('Recorded in the status history for accountability.') }}</p>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                        <div><button class="button-primary" type="submit">{{ __('Record transition') }}</button></div>
                    </form>
                @else
                    <p class="mt-3 text-sm leading-6 text-muted">{{ __('This application is in a final state, so no further transitions are available.') }}</p>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
