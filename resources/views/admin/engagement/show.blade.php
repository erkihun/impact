<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Client engagement') . ' · ' . $submission->reference_no"
            :title="$submission->organization_name ?: $submission->contact_name"
            :description="__('Submitted information is restricted. Share it only with the team responsible for the response.')"
        >
            <x-slot name="action">
                <a class="button-secondary" href="{{ route('admin.engagement.index') }}">{{ __('Back to the queue') }}</a>
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_25rem] lg:items-start">
            <div class="grid gap-6">
                <section class="admin-panel">
                    <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Request') }}</h2>
                    <dl class="mt-5 grid gap-5 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __('Contact') }}</dt>
                            <dd class="mt-1 font-semibold text-brand-950">{{ $submission->contact_name }}</dd>
                            <dd class="mt-1 break-words text-muted">{{ $submission->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __('Request type') }}</dt>
                            <dd class="mt-1 font-semibold text-brand-950">{{ __(str($submission->type->value)->headline()->toString()) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __('Status') }}</dt>
                            <dd class="mt-1"><x-ui.status-badge :status="$submission->status" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __('Received') }}</dt>
                            <dd class="mt-1 font-semibold text-brand-950">
                                @if ($submission->submitted_at)
                                    <time datetime="{{ $submission->submitted_at->toIso8601String() }}">{{ $submission->submitted_at->isoFormat('LLL') }}</time>
                                @else
                                    {{ __('Not recorded') }}
                                @endif
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-muted">{{ __('What they asked for') }}</dt>
                            <dd class="mt-2 whitespace-pre-line leading-7 text-slate-700">{{ $submission->description_encrypted }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="admin-panel">
                    <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Attachments') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-muted">{{ __('A file can be downloaded only after it has passed security scanning. Links expire after five minutes.') }}</p>
                    <ul class="mt-5 grid gap-3">
                        @forelse ($submission->files as $file)
                            <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-300 p-4">
                                <span class="min-w-0 break-words text-sm font-semibold text-brand-950">{{ $file->mediaAsset->original_name }}</span>
                                @if ($file->mediaAsset->scan_status === \App\Enums\MediaStatus::Clean && $file->mediaAsset->processing_status === \App\Enums\MediaStatus::Ready)
                                    <a class="button-tertiary" href="{{ URL::temporarySignedRoute('submission-files.download', now()->addMinutes(5), ['file' => $file]) }}">{{ __('Download') }}</a>
                                @else
                                    <span class="text-sm font-semibold text-state-warning">{{ __('Security processing in progress') }}</span>
                                @endif
                            </li>
                        @empty
                            <li class="text-sm text-muted">{{ __('No files were attached to this request.') }}</li>
                        @endforelse
                    </ul>
                </section>

                <section class="admin-panel">
                    <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Status timeline') }}</h2>
                    @if ($submission->history->isNotEmpty())
                        <ol class="workflow-rail mt-5">
                            @foreach ($submission->history->sortByDesc('created_at') as $event)
                                <li @if ($loop->first) class="is-current" @endif>
                                    <p class="font-bold text-brand-950">
                                        {{ $event->from_status ? __(str($event->from_status->value)->headline()->toString()) : __('Created') }}
                                        <span aria-hidden="true">→</span>
                                        {{ __(str($event->to_status->value)->headline()->toString()) }}
                                    </p>
                                    @if ($event->created_at)
                                        <p class="mt-1 text-sm text-muted">
                                            <time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->isoFormat('LLL') }}</time>
                                        </p>
                                    @endif
                                    @if ($event->note)
                                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ $event->note }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="mt-4 text-sm text-muted">{{ __('No status changes have been recorded yet.') }}</p>
                    @endif
                </section>
            </div>

            <aside class="admin-panel h-fit lg:sticky lg:top-24">
                <h2 class="font-editorial text-lg font-bold text-brand-950">{{ __('Update this request') }}</h2>
                @if ($statuses)
                    <form class="mt-5 grid gap-4" method="POST" action="{{ route('admin.engagement.update', $submission) }}" data-prevent-duplicate>
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="form-label" for="submission-status">{{ __('Move to') }}</label>
                            <select class="form-input" id="submission-status" name="status">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}">{{ __(str($status->value)->headline()->toString()) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="assigned-to">{{ __('Assign to') }}</label>
                            <select class="form-input" id="assigned-to" name="assigned_to">
                                <option value="">{{ __('Keep the current assignee') }}</option>
                                @foreach ($assignees as $assignee)
                                    <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="submission-note">{{ __('Internal note') }}</label>
                            <textarea class="form-input" id="submission-note" name="note" rows="4" maxlength="4000" aria-describedby="submission-note-help"></textarea>
                            <p class="form-help" id="submission-note-help">{{ __('Visible to colleagues only. It is never sent to the person who made the request.') }}</p>
                            <x-input-error :messages="$errors->get('note')" class="mt-2" />
                        </div>
                        <div><button class="button-primary justify-center" type="submit">{{ __('Save update') }}</button></div>
                    </form>
                @else
                    <p class="mt-3 text-sm leading-6 text-muted">{{ __('This request is in a final state, so no further transitions are available.') }}</p>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
