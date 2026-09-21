<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Immutable revision').' · '.strtoupper($content->currentVersion->locale)"
            :title="__('Edit :title', ['title' => $content->currentVersion->title])"
            :description="__('Saving creates a new draft revision. The current version remains in the audit history.')"
        />
    </x-slot>

    <div class="admin-workspace">
        <form method="POST" action="{{ route('admin.content.update', $content) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="expected_current_version_id" value="{{ $content->currentVersion->id }}">

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                <section class="admin-panel border-t-4 border-brand-950" aria-labelledby="editor-canvas-title">
                    <div class="editorial-rule-heading">
                        <div>
                            <p class="eyebrow">{{ __('Editorial canvas') }}</p>
                            <h2 id="editor-canvas-title" class="mt-2 text-xl font-bold text-brand-950">{{ __('Public-facing content') }}</h2>
                        </div>
                        <x-ui.status-badge :status="$content->status" />
                    </div>
                    <x-ui.error-summary class="mt-6" :errors="$errors" />
                    <div class="mt-7 grid gap-7">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div><label class="form-label" for="content-locale">{{ __('Locale') }}</label><input class="form-input bg-quiet" id="content-locale" value="{{ strtoupper($content->currentVersion->locale) }}" disabled></div>
                            <div><label class="form-label" for="content-slug">{{ __('Stable URL slug') }}</label><input class="form-input bg-quiet" id="content-slug" value="{{ $content->currentVersion->slug }}" disabled></div>
                        </div>
                        <div>
                            <label class="form-label" for="content-title">{{ __('Title') }}</label>
                            <input class="form-input text-lg font-bold" id="content-title" name="title" value="{{ old('title', $content->currentVersion->title) }}" maxlength="220" required>
                            @error('title')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="content-summary">{{ __('Summary') }}</label>
                            <p class="form-help mb-2">{{ __('A concise orientation used in public collection and detail views.') }}</p>
                            <textarea class="form-input" id="content-summary" name="summary" rows="4" maxlength="2000">{{ old('summary', $content->currentVersion->summary) }}</textarea>
                            @error('summary')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="content-body">{{ __('Body') }}</label>
                            <p class="form-help mb-2">{{ __('Structure the argument for reading, not for decoration. Publication remains governed by workflow.') }}</p>
                            <textarea class="form-input font-mono text-sm leading-7" id="content-body" name="body" rows="20" maxlength="100000" required>{{ old('body', data_get($content->currentVersion->body, 'content')) }}</textarea>
                            @error('body')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <aside class="admin-attention-rail lg:sticky lg:top-24" aria-labelledby="revision-controls-title">
                    <p class="eyebrow">{{ __('Revision control') }}</p>
                    <h2 id="revision-controls-title" class="mt-4 font-editorial text-xl font-bold text-brand-950">{{ __('Create the next immutable draft') }}</h2>
                    <dl class="mt-6 divide-y divide-edge text-sm">
                        <div class="py-3"><dt class="font-bold text-brand-950">{{ __('Current version') }}</dt><dd class="mt-1 text-muted">{{ __('Version :number', ['number' => $content->currentVersion->version_no]) }}</dd></div>
                        <div class="py-3"><dt class="font-bold text-brand-950">{{ __('Current state') }}</dt><dd class="mt-2"><x-ui.status-badge :status="$content->status" /></dd></div>
                        <div class="py-3"><dt class="font-bold text-brand-950">{{ __('Stable slug') }}</dt><dd class="mt-1 break-all font-mono text-xs text-muted">{{ $content->currentVersion->slug }}</dd></div>
                    </dl>
                    <div class="mt-7 grid gap-3">
                        <button class="button-primary justify-center" type="submit">{{ __('Create draft revision') }}</button>
                        <a class="button-secondary justify-center" href="{{ route('admin.content.show', $content) }}">{{ __('Cancel') }}</a>
                    </div>
                    <p class="mt-5 text-xs leading-6 text-muted">{{ __('This action does not publish content. Publication requires an authorized workflow transition.') }}</p>
                </aside>
            </div>
        </form>
    </div>
</x-app-layout>
