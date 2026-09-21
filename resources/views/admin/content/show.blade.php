<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="str($content->type->value)->headline().' · '.strtoupper($content->currentVersion->locale)"
            :title="$content->currentVersion->title"
        >
            <x-slot name="action">
                <a class="button-secondary" href="{{ $previewUrls[$content->currentVersion->id] }}" target="_blank" rel="noopener">{{ __('Preview') }}</a>
                @can('update', $content)
                    <a class="button-primary" href="{{ route('admin.content.edit', $content) }}">{{ __('Edit revision') }}</a>
                @endcan
                <x-ui.status-badge :status="$content->status" />
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        <div class="grid gap-8 lg:grid-cols-[1fr_24rem]">
            <article class="admin-panel border-t-4 border-brand-950">
                <x-ui.insight-marker :label="__('Current revision')" />
                <p class="impact-quote mt-7 text-lg leading-8 text-brand-950">{{ $content->currentVersion->summary }}</p>
                <div class="prose mt-10 max-w-none prose-headings:font-editorial prose-headings:text-brand-950 prose-a:text-action-700">{{ data_get($content->currentVersion->body, 'content') }}</div>
            </article>
            <div class="grid h-fit gap-6">
                <section class="admin-attention-rail">
                    <p class="eyebrow">{{ __('Workflow control') }}</p>
                    <h2 class="mt-3 font-editorial text-xl font-bold text-brand-950">{{ __('Move the record deliberately') }}</h2>
                    <p class="mt-3 text-sm text-muted">{{ __('Current state') }}: <x-ui.status-badge class="ms-2" :status="$content->status" /></p>
                    @if ($allowedTransitions)
                        <form class="mt-5 grid gap-4" method="POST" action="{{ route('admin.content.transition', $content) }}">
                            @csrf
                            <input type="hidden" name="content_version_id" value="{{ $content->currentVersion->id }}">
                            <label class="form-label" for="transition-to">{{ __('Move to') }}</label>
                            <select class="form-input" id="transition-to" name="to">
                                @foreach ($allowedTransitions as $state)
                                    <option value="{{ $state->value }}">{{ str($state->value)->headline() }}</option>
                                @endforeach
                            </select>
                            <label class="form-label" for="publish-at">{{ __('Publish at') }}</label>
                            <input class="form-input" id="publish-at" name="publish_at" type="datetime-local" value="{{ old('publish_at') }}">
                            <p class="text-xs text-muted">{{ __('Required only when scheduling publication.') }}</p>
                            <label class="form-label" for="unpublish-at">{{ __('Unpublish at') }}</label>
                            <input class="form-input" id="unpublish-at" name="unpublish_at" type="datetime-local" value="{{ old('unpublish_at') }}">
                            <label class="form-label" for="transition-note">{{ __('Reason or note') }}</label>
                            <textarea class="form-input" id="transition-note" name="note" maxlength="2000"></textarea>
                            <button class="button-primary justify-center" type="submit">{{ __('Apply transition') }}</button>
                        </form>
                    @else
                        <p class="mt-4 text-sm text-muted">{{ __('No further transitions are available.') }}</p>
                    @endif
                </section>

                <section class="admin-panel border-t-4 border-knowledge-700">
                    <p class="eyebrow">{{ __('Revision history') }}</p>
                    <h2 class="mt-3 font-editorial text-xl font-bold text-brand-950">{{ __('Immutable versions') }}</h2>
                    <ol class="workflow-rail mt-5">
                        @foreach ($content->versions->sortByDesc('version_no') as $version)
                            <li @class(['p-4 text-sm', 'is-current' => $version->id === $content->current_version_id])>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p><strong>{{ __('Version :number', ['number' => $version->version_no]) }}</strong> · {{ $version->title }}</p>
                                    <a class="font-semibold text-impact-700 hover:underline" href="{{ $previewUrls[$version->id] }}" target="_blank" rel="noopener">{{ __('Preview') }}</a>
                                </div>
                                <p class="mt-1 text-muted">{{ str($version->workflow_state->value)->headline() }} · {{ $version->created_at }}</p>
                                @can('rollback', $content)
                                    @if ($version->id !== $content->current_version_id && in_array($version->id, $rollbackVersionIds, true))
                                        <form class="mt-3 grid gap-2" method="POST" action="{{ route('admin.content.rollback', $content) }}">
                                            @csrf
                                            <input type="hidden" name="expected_current_version_id" value="{{ $content->current_version_id }}">
                                            <input type="hidden" name="source_version_id" value="{{ $version->id }}">
                                            <label class="form-label" for="rollback-reason-{{ $version->id }}">{{ __('Rollback reason') }}</label>
                                            <input class="form-input" id="rollback-reason-{{ $version->id }}" name="reason" minlength="10" maxlength="2000" required>
                                            <button class="button-secondary" type="submit">{{ __('Restore as new draft') }}</button>
                                        </form>
                                    @endif
                                @endcan
                            </li>
                        @endforeach
                    </ol>
                </section>

                <section class="admin-panel border-t-4 border-gold-400">
                    <p class="eyebrow">{{ __('Audit trail') }}</p>
                    <h2 class="mt-3 font-editorial text-xl font-bold text-brand-950">{{ __('Timeline') }}</h2>
                    <ol class="workflow-rail mt-5">
                        @foreach ($content->workflowEvents->sortByDesc('created_at') as $event)
                            <li class="py-3 ps-4 text-sm">
                                <strong>{{ str($event->to_state->value)->headline() }}</strong>
                                <p class="text-muted">{{ $event->created_at }} · {{ $event->actor?->name }}</p>
                                @if ($event->note)
                                    <p class="mt-1">{{ $event->note }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
