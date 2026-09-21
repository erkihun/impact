<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            :eyebrow="__('Page composer · :locale', ['locale' => strtoupper($composition->locale)])"
            :title="str($composition->page_key)->headline()->toString()"
            :description="__('Edit the page through approved section types. Every save creates an immutable section version.')"
        >
            <x-slot name="action">
                <div class="flex flex-wrap gap-3">
                    <a class="button-secondary" href="{{ $previewUrl }}" target="_blank" rel="noopener">{{ __('Secure preview') }}</a>
                    @unless ($composition->isEditable())
                        <form method="POST" action="{{ route('admin.page-compositions.drafts.store', $composition) }}">@csrf<button class="button-primary" type="submit">{{ __('Create editable draft') }}</button></form>
                    @endunless
                </div>
            </x-slot>
        </x-admin.page-header>
    </x-slot>

    <div class="admin-workspace">
        <x-admin.error-summary :errors="$errors" />

        <div class="grid gap-5 xl:grid-cols-[17rem_minmax(0,1fr)_19rem]">
            <aside class="h-fit border border-edge bg-white p-4 xl:sticky xl:top-24">
                <p class="eyebrow">{{ __('Section library') }}</p>
                <h2 class="mt-3 font-editorial text-xl font-bold text-brand-950">{{ __('Add controlled section') }}</h2>
                @unless ($composition->isEditable())
                    <x-admin.alert class="mt-5" tone="information" :title="__('Published version is read-only')">{{ __('Create a draft to add, reorder or edit sections.') }}</x-admin.alert>
                @endunless
                <form class="mt-5 grid gap-4" method="POST" action="{{ route('admin.page-compositions.sections.store', $composition) }}">
                    <fieldset class="contents" @disabled(! $composition->isEditable())>
                    @csrf
                    <input type="hidden" name="lock_version" value="{{ $composition->lock_version }}">
                    <input type="hidden" name="visibility_rule" value="always">
                    <input type="hidden" name="enabled" value="1">
                    <div>
                        <label class="form-label" for="new-section-type">{{ __('Section type') }}</label>
                        <select class="form-input" id="new-section-type" name="type" required>
                            @foreach ($registry as $type => $definition)<option value="{{ $type }}">{{ __($definition['name']) }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="new-section-variant">{{ __('Approved variant') }}</label>
                        <select class="form-input" id="new-section-variant" name="variant">
                            @foreach (collect($registry)->pluck('variants')->flatten()->unique() as $variant)<option value="{{ $variant }}">{{ str($variant)->headline() }}</option>@endforeach
                        </select>
                        <p class="form-help">{{ __('The server validates that the selected variant belongs to the section type.') }}</p>
                    </div>
                    <div><label class="form-label" for="new-section-label">{{ __('Editor label') }}</label><input class="form-input" id="new-section-label" name="editor_label" required></div>
                    <div><label class="form-label" for="new-section-heading">{{ __('Heading') }}</label><input class="form-input" id="new-section-heading" name="content[heading]"></div>
                    <div><label class="form-label" for="new-section-summary">{{ __('Summary') }}</label><textarea class="form-input" id="new-section-summary" name="content[summary]" rows="4"></textarea></div>
                    <input type="hidden" name="presentation[surface_tone]" value="default">
                    <input type="hidden" name="presentation[container_width]" value="standard">
                    <input type="hidden" name="presentation[spacing_top]" value="standard">
                    <input type="hidden" name="media_selection_present" value="1">
                    @if ($eligibleMedia->isNotEmpty())
                        <div>
                            <label class="form-label" for="new-section-media">{{ __('Approved media') }}</label>
                            <select class="form-input" id="new-section-media" name="media_asset_ids[]" multiple size="5">
                                @foreach ($eligibleMedia as $asset)<option value="{{ $asset->id }}">{{ $asset->title ?: $asset->original_name }}</option>@endforeach
                            </select>
                            <p class="form-help">{{ __('Select from the processed media library. Uploads remain in the existing secure media workflow.') }}</p>
                        </div>
                    @endif
                    <button class="button-primary" type="submit">{{ __('Add section') }}</button>
                    </fieldset>
                </form>
            </aside>

            <main class="min-w-0">
                <div class="flex items-center justify-between gap-4">
                    <div><p class="eyebrow">{{ __('Page outline') }}</p><h2 class="mt-2 font-editorial text-2xl font-bold text-brand-950">{{ trans_choice(':count active section|:count active sections', $composition->sections->count(), ['count' => $composition->sections->count()]) }}</h2></div>
                    <x-ui.status-badge :status="$composition->state" />
                </div>

                <div class="mt-5 grid gap-4">
                    @forelse ($composition->sections as $section)
                        @php
                            $version = $section->currentVersion;
                        @endphp
                        <details class="border border-edge bg-white shadow-sm" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5">
                                <span><span class="font-bold text-brand-950">{{ $section->editor_label }}</span><span class="mt-1 block text-xs uppercase tracking-wide text-muted">{{ str($version->type->value)->headline() }} · v{{ $version->version_no }}</span></span>
                                <span class="text-sm font-bold text-action-700">{{ __('Edit') }}</span>
                            </summary>
                            <form class="grid gap-5 border-t border-edge p-5" method="POST" action="{{ route('admin.page-compositions.sections.update', [$composition, $section]) }}">
                                <fieldset class="contents" @disabled(! $composition->isEditable())>
                                @csrf @method('PATCH')
                                <input type="hidden" name="lock_version" value="{{ $composition->lock_version }}">
                                <input type="hidden" name="type" value="{{ $version->type->value }}">
                                <input type="hidden" name="variant" value="{{ $version->variant }}">
                                <input type="hidden" name="visibility_rule" value="{{ $version->visibility_rule->value }}">
                                <input type="hidden" name="enabled" value="0"><label class="inline-flex items-center gap-2"><input type="checkbox" name="enabled" value="1" @checked($version->enabled)> {{ __('Enabled') }}</label>
                                <div><label class="form-label">{{ __('Editor label') }}</label><input class="form-input" name="editor_label" value="{{ $section->editor_label }}" required></div>
                                @foreach ($registry[$version->type->value]['fields'] as $field)
                                    <div>
                                        <label class="form-label" for="section-{{ $section->id }}-{{ $field }}">{{ str($field)->headline() }}</label>
                                        @if ($field === 'items')
                                            <textarea class="form-input font-mono text-xs" id="section-{{ $section->id }}-{{ $field }}" name="content[{{ $field }}]" rows="7">{{ json_encode($version->content[$field] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                        @elseif (in_array($field, ['body', 'summary', 'privacy_guidance'], true))
                                            <textarea class="form-input" id="section-{{ $section->id }}-{{ $field }}" name="content[{{ $field }}]" rows="5">{{ $version->content[$field] ?? '' }}</textarea>
                                        @else
                                            <input class="form-input" id="section-{{ $section->id }}-{{ $field }}" name="content[{{ $field }}]" value="{{ $version->content[$field] ?? '' }}">
                                        @endif
                                    </div>
                                @endforeach
                                @foreach ($version->presentation as $key => $value)<input type="hidden" name="presentation[{{ $key }}]" value="{{ $value }}">@endforeach
                                <input type="hidden" name="media_selection_present" value="1">
                                @if ($registry[$version->type->value]['media'] !== [])
                                    <div>
                                        <label class="form-label" for="section-{{ $section->id }}-media">{{ __('Approved media') }}</label>
                                        <select class="form-input" id="section-{{ $section->id }}-media" name="media_asset_ids[]" multiple size="6">
                                            @foreach ($eligibleMedia as $asset)
                                                <option value="{{ $asset->id }}" @selected($version->media->contains('media_asset_id', $asset->id))>{{ $asset->title ?: $asset->original_name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="form-help">{{ __('No URL entry is accepted. Choose only clean, processed public assets.') }}</p>
                                    </div>
                                @endif
                                <div class="flex flex-wrap gap-3">
                                    <button class="button-primary" type="submit">{{ __('Save new version') }}</button>
                                </div>
                                </fieldset>
                            </form>
                            @if ($composition->isEditable())
                            <div class="flex flex-wrap gap-3 border-t border-edge px-5 py-4">
                                <form method="POST" action="{{ route('admin.page-compositions.sections.duplicate', [$composition, $section]) }}">@csrf<input type="hidden" name="lock_version" value="{{ $composition->lock_version }}"><button class="button-tertiary" type="submit">{{ __('Duplicate') }}</button></form>
                                @unless ($section->required || $section->locked)
                                    <form method="POST" action="{{ route('admin.page-compositions.sections.destroy', [$composition, $section]) }}">@csrf @method('DELETE')<input type="hidden" name="lock_version" value="{{ $composition->lock_version }}"><button class="button-tertiary text-red-700" type="submit">{{ __('Archive') }}</button></form>
                                @endunless
                            </div>
                            @endif
                        </details>
                    @empty
                        <x-admin.empty-state :title="__('This page has no sections.')" :description="__('Use the controlled section library to establish its semantic structure.')" />
                    @endforelse
                </div>
            </main>

            <aside class="h-fit border border-edge bg-paper p-5 xl:sticky xl:top-24">
                <p class="eyebrow">{{ __('Page controls') }}</p>
                <dl class="mt-5 divide-y divide-edge text-sm">
                    <div class="py-3"><dt class="font-bold">{{ __('Template') }}</dt><dd class="mt-1 text-muted">{{ str($composition->template_type->value)->headline() }}</dd></div>
                    <div class="py-3"><dt class="font-bold">{{ __('Composition version') }}</dt><dd class="mt-1 text-muted">v{{ $composition->version_no }}</dd></div>
                    <div class="py-3"><dt class="font-bold">{{ __('Edit lock') }}</dt><dd class="mt-1 text-muted">{{ $composition->lock_version }}</dd></div>
                    <div class="py-3"><dt class="font-bold">{{ __('Locale') }}</dt><dd class="mt-1 text-muted">{{ strtoupper($composition->locale) }}</dd></div>
                </dl>
                <p class="mt-5 text-sm leading-6 text-muted">{{ __('Publication remains controlled by review and publisher permissions. Preview links are signed, expire, and are marked noindex.') }}</p>
                @php
                    $nextStates = match ($composition->state) {
                        \App\Enums\PageCompositionState::Draft,
                        \App\Enums\PageCompositionState::ChangesRequested => ['in_review'],
                        \App\Enums\PageCompositionState::InReview => auth()->user()->hasPermission('pages.approve') ? ['changes_requested', 'approved'] : [],
                        \App\Enums\PageCompositionState::Approved => auth()->user()->hasPermission('pages.publish') ? ['scheduled', 'published'] : [],
                        \App\Enums\PageCompositionState::Scheduled => auth()->user()->hasPermission('pages.publish') ? ['published', 'archived'] : [],
                        \App\Enums\PageCompositionState::Published => auth()->user()->hasPermission('pages.publish') ? ['archived'] : [],
                        default => [],
                    };
                @endphp
                @if ($nextStates !== [])
                    <form class="mt-6 grid gap-4 border-t border-edge pt-5" method="POST" action="{{ route('admin.page-compositions.transitions.store', $composition) }}">
                        @csrf
                        <div>
                            <label class="form-label" for="composition-transition">{{ __('Workflow action') }}</label>
                            <select class="form-input" id="composition-transition" name="to" required>
                                @foreach ($nextStates as $state)<option value="{{ $state }}">{{ str($state)->headline() }}</option>@endforeach
                            </select>
                        </div>
                        <div><label class="form-label" for="composition-comment">{{ __('Review comment') }}</label><textarea class="form-input" id="composition-comment" name="comment" rows="4"></textarea></div>
                        <button class="button-primary" type="submit">{{ __('Apply workflow action') }}</button>
                    </form>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
