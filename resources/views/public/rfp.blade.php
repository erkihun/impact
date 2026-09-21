@extends('layouts.public')

@section('title', __('Submit an RFP — Impact Consulting'))

@section('meta_description', __('Request a confidential proposal from Impact Consulting.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[__('Home') => route('localized-home', ['locale' => app()->getLocale()]), __('Submit an RFP') => null]" />
@endsection

@section('content')
    @if ($managedComposition)
        <x-ui.page-composition :composition="$managedComposition" />
    @else
    <x-ui.page-header
        :eyebrow="__('Request for proposal')"
        :title="__('Share your brief securely.')"
        :description="__('Tell us about the assignment, expected outcomes and timing. Supporting files remain private and unavailable until security processing is complete.')"
    />
    @endif

    @php
        $initialStep = match (true) {
            $errors->has('description') => 1,
            $errors->hasAny(['contact_name', 'email', 'organization_name', 'role']) => 2,
            $errors->hasAny(['timeframe', 'budget_range', 'attachments', 'attachments.*']) => 3,
            $errors->has('privacy_acknowledged') => 4,
            default => 1,
        };
    @endphp

    <section class="public-section impact-editorial-surface">
        <div class="public-engagement-layout content-container lg:grid-cols-[minmax(0,1fr)_20rem]" x-data="multiStepForm(4, {{ $initialStep }})">
            <div>
            <ol class="process-tabs" aria-label="{{ __('RFP submission progress') }}">
                @foreach ([__('Need'), __('Organization'), __('Project'), __('Review')] as $stepLabel)
                    <li>
                        <button
                            class="flex min-h-11 w-full items-center gap-2 rounded-lg px-2 text-start text-xs font-bold"
                            type="button"
                            data-step-target="{{ $loop->iteration }}"
                            x-on:click="goToStep"
                            x-bind:class="stepClass{{ $loop->iteration }}"
                            x-bind:aria-current="stepCurrent{{ $loop->iteration }}"
                        >
                            <span class="grid size-6 shrink-0 place-items-center rounded-full border border-current">{{ $loop->iteration }}</span>
                            <span class="sr-only sm:not-sr-only">{{ $stepLabel }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>

            <form
                x-ref="form"
                method="POST"
                enctype="multipart/form-data"
                action="{{ route('rfp-requests.store', ['locale' => app()->getLocale()]) }}"
                class="engagement-form-canvas"
                data-prevent-duplicate
            >
                @csrf
                <input type="hidden" name="type" value="rfp">
                <input type="hidden" name="policy_version" value="{{ $publicExperience['privacy']['policy_version'] }}">
                <div class="hidden" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>

                <x-ui.error-summary class="mb-8" :errors="$errors" />
                <p class="mb-7 text-sm leading-6 text-muted">{{ __('Fields marked required must be completed. Do not include credentials, passwords or unnecessary personal data.') }}</p>

                <section data-step="1" x-show="onStep1" aria-labelledby="rfp-step-1">
                    <p class="eyebrow">{{ __('Step 1 of 4') }}</p>
                    <h2 id="rfp-step-1" class="heading-3 mt-3" tabindex="-1" data-step-heading>{{ __('What assignment should the proposal address?') }}</h2>
                    <div class="mt-6">
                        <label class="form-label" for="description">{{ __('Assignment description') }} <span class="form-required">({{ __('required') }})</span></label>
                        <p class="form-help mb-2" id="description-help">{{ __('Describe the context, desired outcomes, expected deliverables and any material constraints.') }}</p>
                        <textarea class="form-input min-h-48" id="description" name="description" required minlength="20" maxlength="10000" aria-describedby="description-help @error('description') description-error @enderror" @error('description') aria-invalid="true" @enderror>{{ old('description') }}</textarea>
                        @error('description')<p class="field-error" id="description-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section data-step="2" x-cloak x-show="onStep2" aria-labelledby="rfp-step-2">
                    <p class="eyebrow">{{ __('Step 2 of 4') }}</p>
                    <h2 id="rfp-step-2" class="heading-3 mt-3" tabindex="-1" data-step-heading>{{ __('Who owns this request?') }}</h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        <div><label class="form-label" for="contact_name">{{ __('Full name') }} <span class="form-required">({{ __('required') }})</span></label><input class="form-input" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required maxlength="160" autocomplete="name" @error('contact_name') aria-invalid="true" @enderror>@error('contact_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label" for="email">{{ __('Work email') }} <span class="form-required">({{ __('required') }})</span></label><input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" @error('email') aria-invalid="true" @enderror>@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label" for="organization_name">{{ __('Organization') }}</label><input class="form-input" id="organization_name" name="organization_name" value="{{ old('organization_name') }}" maxlength="200" autocomplete="organization"></div>
                        <div><label class="form-label" for="role">{{ __('Role') }}</label><input class="form-input" id="role" name="role" value="{{ old('role') }}" maxlength="160" autocomplete="organization-title"></div>
                    </div>
                </section>

                <section data-step="3" x-cloak x-show="onStep3" aria-labelledby="rfp-step-3">
                    <p class="eyebrow">{{ __('Step 3 of 4') }}</p>
                    <h2 id="rfp-step-3" class="heading-3 mt-3" tabindex="-1" data-step-heading>{{ __('What timing, budget and evidence should we review?') }}</h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        <div><label class="form-label" for="timeframe">{{ __('Timeframe') }}</label><p class="form-help mb-2">{{ __('Include the proposal deadline and expected delivery window when known.') }}</p><input class="form-input" id="timeframe" name="timeframe" value="{{ old('timeframe') }}" maxlength="100"></div>
                        <div><label class="form-label" for="budget_range">{{ __('Indicative budget') }}</label><p class="form-help mb-2">{{ __('A range is optional and helps us shape a proportionate response.') }}</p><input class="form-input" id="budget_range" name="budget_range" value="{{ old('budget_range') }}" maxlength="100"></div>
                        <x-ui.file-upload
                            class="sm:col-span-2"
                            id="attachments"
                            name="attachments[]"
                            :label="__('Supporting files')"
                            accept=".pdf,.docx,.xlsx,.pptx"
                            :help="__('Up to 5 PDF, DOCX, XLSX or PPTX files; 20 MB per file. Files remain private and are security scanned before staff can access them.')"
                            multiple
                        />
                    </div>
                    <div class="status-information mt-6">{{ __('Selecting a file does not mean it has been approved. After submission, each file remains unavailable until security processing is complete.') }}</div>
                </section>

                <section data-step="4" x-cloak x-show="onStep4" aria-labelledby="rfp-step-4">
                    <p class="eyebrow">{{ __('Step 4 of 4') }}</p>
                    <h2 id="rfp-step-4" class="heading-3 mt-3" tabindex="-1" data-step-heading>{{ __('Review and submit the brief') }}</h2>
                    <dl class="mt-6 grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-2">
                        @foreach ([
                            __('Assignment description') => 'description',
                            __('Full name') => 'contact_name',
                            __('Work email') => 'email',
                            __('Organization') => 'organization_name',
                            __('Timeframe') => 'timeframe',
                            __('Indicative budget') => 'budget_range',
                        ] as $label => $field)
                            <div class="bg-white p-4"><dt class="text-xs font-bold uppercase tracking-wide text-muted">{{ $label }}</dt><dd class="mt-2 whitespace-pre-line text-sm text-ink" data-summary-field="{{ $field }}"></dd></div>
                        @endforeach
                    </dl>
                    <label class="mt-7 flex items-start gap-3 text-sm leading-6 text-slate-700">
                        <input id="privacy_acknowledged" class="mt-1 rounded border-slate-300 text-action-700 focus:ring-knowledge-600" type="checkbox" name="privacy_acknowledged" value="1" required @checked(old('privacy_acknowledged'))>
                        <span>{{ __('I understand that these details and files will be used to assess and respond to this request under the current privacy notice.') }}</span>
                    </label>
                    @error('privacy_acknowledged')<p class="field-error">{{ $message }}</p>@enderror
                    <div class="status-information mt-6">{{ __('After submission, the confirmation screen provides the authoritative reference. Keep that reference for follow-up.') }}</div>
                </section>

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-between">
                    <button class="button-secondary" type="button" x-show="canGoBack" x-on:click="previousStep">{{ __('Previous step') }}</button>
                    <span class="hidden sm:block" x-show="onStep1"></span>
                    <button class="button-primary" type="button" x-show="canGoForward" x-on:click="nextStep">{{ __('Continue') }} →</button>
                    <button class="button-primary" type="submit" x-show="onFinalStep">{{ __('Submit RFP') }} →</button>
                </div>
            </form>
            </div>
            <aside class="form-context-panel lg:sticky lg:top-32" aria-labelledby="rfp-context-title">
                <x-ui.insight-marker :label="__('Secure proposal intake')" tone="gold" />
                <h2 id="rfp-context-title" class="mt-5 font-editorial text-2xl font-bold text-brand-950">{{ __('A useful brief makes the evaluation criteria visible.') }}</h2>
                <p class="mt-4 text-sm leading-7 text-muted">{{ __('Include the assignment context, desired outcomes, constraints and proposal deadline. Avoid passwords and unnecessary personal data.') }}</p>
                <ol class="workflow-rail mt-7">
                    @foreach ([__('Assignment and outcomes'), __('Decision ownership'), __('Timing and budget'), __('Relevant supporting files')] as $contextItem)
                        <li class="py-3"><span class="font-mono text-xs font-bold text-action-700">0{{ $loop->iteration }}</span><span class="ms-3 text-sm font-semibold text-brand-950">{{ $contextItem }}</span></li>
                    @endforeach
                </ol>
                <div class="mt-7 border-t border-slate-300 pt-6">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-muted">{{ __('File handling') }}</p>
                    <p class="mt-3 text-sm leading-7 text-muted">{{ __('Files remain private and unavailable to staff until the configured security processing is complete.') }}</p>
                </div>
            </aside>
        </div>
    </section>
@endsection
