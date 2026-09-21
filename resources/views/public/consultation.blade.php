@extends('layouts.public')

@section('title', __('Request a consultation — Impact Consulting'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[__('Home') => route('localized-home', ['locale' => app()->getLocale()]), __('Request a consultation') => null]" />
@endsection

@section('content')
    @if ($managedComposition)
        <x-ui.page-composition :composition="$managedComposition" />
    @else
    <x-ui.page-header
        :eyebrow="__('Start a conversation')"
        :title="__('Tell us what success needs to look like.')"
        :description="__('Share enough context for us to route your request. A member of our team will respond using the contact details you provide.')"
    />
    @endif

    @php
        $initialStep = match (true) {
            $errors->hasAny(['contact_name', 'email', 'organization_name', 'role', 'phone']) => 2,
            $errors->hasAny(['timeframe', 'budget_range']) => 3,
            $errors->has('privacy_acknowledged') => 4,
            default => 1,
        };
    @endphp

    <section class="public-section impact-editorial-surface">
        <div class="public-engagement-layout content-container lg:grid-cols-[minmax(0,1fr)_20rem]" x-data="multiStepForm(4, {{ $initialStep }})">
            <div>
            <ol class="process-tabs" aria-label="{{ __('Consultation request progress') }}">
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
                action="{{ route('consultation-requests.store', ['locale' => app()->getLocale()]) }}"
                class="engagement-form-canvas"
                data-prevent-duplicate
            >
                @csrf
                <input type="hidden" name="type" value="consultation">
                <input type="hidden" name="policy_version" value="{{ $publicExperience['privacy']['policy_version'] }}">
                @if (request()->filled('service_id'))<input type="hidden" name="service_id" value="{{ request('service_id') }}">@endif
                @if (request()->filled('industry_id'))<input type="hidden" name="industry_id" value="{{ request('industry_id') }}">@endif
                <div class="hidden" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>

                <x-ui.error-summary class="mb-8" :errors="$errors" />
                <p class="mb-7 text-sm leading-6 text-muted">{{ __('Fields marked required must be completed. We request only information needed to route and respond to your inquiry.') }}</p>

                <section data-step="1" x-show="onStep1" aria-labelledby="consultation-step-1">
                    <p class="eyebrow">{{ __('Step 1 of 4') }}</p>
                    <h2 id="consultation-step-1" class="heading-3 mt-3" tabindex="-1" data-step-heading>{{ __('What outcome or challenge should we understand?') }}</h2>
                    @if (request()->filled('service_id') || request()->filled('industry_id'))
                        <div class="status-information mt-5">{{ __('This request includes context selected from the page you were viewing. You can still describe a different need below.') }}</div>
                    @endif
                    <div class="mt-6">
                        <label class="form-label" for="description">{{ __('Challenge summary') }} <span class="form-required">({{ __('required') }})</span></label>
                        <p class="form-help mb-2" id="description-help">{{ __('Describe the decision, problem or result you want help with. You do not need to know the internal service name.') }}</p>
                        <textarea class="form-input min-h-48" id="description" name="description" required minlength="20" maxlength="10000" aria-describedby="description-help @error('description') description-error @enderror" @error('description') aria-invalid="true" @enderror>{{ old('description') }}</textarea>
                        @error('description')<p class="field-error" id="description-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section data-step="2" x-cloak x-show="onStep2" aria-labelledby="consultation-step-2">
                    <p class="eyebrow">{{ __('Step 2 of 4') }}</p>
                    <h2 id="consultation-step-2" class="heading-3 mt-3" tabindex="-1" data-step-heading>{{ __('Who should we respond to?') }}</h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        <div><label class="form-label" for="contact_name">{{ __('Full name') }} <span class="form-required">({{ __('required') }})</span></label><input class="form-input" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required maxlength="160" autocomplete="name" @error('contact_name') aria-invalid="true" @enderror>@error('contact_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label" for="email">{{ __('Work email') }} <span class="form-required">({{ __('required') }})</span></label><input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" @error('email') aria-invalid="true" @enderror>@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label" for="organization_name">{{ __('Organization') }}</label><input class="form-input" id="organization_name" name="organization_name" value="{{ old('organization_name') }}" maxlength="200" autocomplete="organization"></div>
                        <div><label class="form-label" for="role">{{ __('Role') }}</label><input class="form-input" id="role" name="role" value="{{ old('role') }}" maxlength="160" autocomplete="organization-title"></div>
                        <div class="sm:col-span-2"><label class="form-label" for="phone">{{ __('Phone') }}</label><input class="form-input" id="phone" name="phone" value="{{ old('phone') }}" maxlength="40" autocomplete="tel"></div>
                    </div>
                </section>

                <section data-step="3" x-cloak x-show="onStep3" aria-labelledby="consultation-step-3">
                    <p class="eyebrow">{{ __('Step 3 of 4') }}</p>
                    <h2 id="consultation-step-3" class="heading-3 mt-3" tabindex="-1" data-step-heading>{{ __('What timing and scope should we consider?') }}</h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        <div><label class="form-label" for="timeframe">{{ __('Timeframe') }}</label><p class="form-help mb-2">{{ __('For example: next quarter or before a stated decision date.') }}</p><input class="form-input" id="timeframe" name="timeframe" value="{{ old('timeframe') }}" maxlength="100"></div>
                        <div><label class="form-label" for="budget_range">{{ __('Indicative budget') }}</label><p class="form-help mb-2">{{ __('A range is optional and helps us suggest an appropriate response.') }}</p><input class="form-input" id="budget_range" name="budget_range" value="{{ old('budget_range') }}" maxlength="100"></div>
                    </div>
                </section>

                <section data-step="4" x-cloak x-show="onStep4" aria-labelledby="consultation-step-4">
                    <p class="eyebrow">{{ __('Step 4 of 4') }}</p>
                    <h2 id="consultation-step-4" class="heading-3 mt-3" tabindex="-1" data-step-heading>{{ __('Review and send your request') }}</h2>
                    <dl class="mt-6 grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-2">
                        @foreach ([
                            __('Challenge summary') => 'description',
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
                        <span>{{ __('I understand that Impact Consulting will use these details to assess and respond to this request under the current privacy notice.') }}</span>
                    </label>
                    @error('privacy_acknowledged')<p class="field-error">{{ $message }}</p>@enderror
                    <div class="status-information mt-6">{{ __('After submission, you will receive an authoritative reference and an explanation of what happens next.') }}</div>
                </section>

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-between">
                    <button class="button-secondary" type="button" x-show="canGoBack" x-on:click="previousStep">{{ __('Previous step') }}</button>
                    <span class="hidden sm:block" x-show="onStep1"></span>
                    <button class="button-primary" type="button" x-show="canGoForward" x-on:click="nextStep">{{ __('Continue') }} →</button>
                    <button class="button-primary" type="submit" x-show="onFinalStep">{{ __('Send consultation request') }} →</button>
                </div>
            </form>
            </div>
            <aside class="form-context-panel lg:sticky lg:top-32" aria-labelledby="consultation-context-title">
                <x-ui.insight-marker :label="__('Before you begin')" />
                <h2 id="consultation-context-title" class="mt-5 font-editorial text-2xl font-bold text-brand-950">{{ __('The clearest requests start with the decision.') }}</h2>
                <p class="mt-4 text-sm leading-7 text-muted">{{ __('You do not need to diagnose the solution. Tell us what must change, who is affected and when the decision matters.') }}</p>
                <ol class="workflow-rail mt-7">
                    @foreach ([__('The outcome or decision'), __('The operating context'), __('The people involved'), __('The relevant timing')] as $contextItem)
                        <li class="py-3"><span class="font-mono text-xs font-bold text-action-700">0{{ $loop->iteration }}</span><span class="ms-3 text-sm font-semibold text-brand-950">{{ $contextItem }}</span></li>
                    @endforeach
                </ol>
                <div class="mt-7 border-t border-slate-300 pt-6">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-muted">{{ __('What happens next') }}</p>
                    <p class="mt-3 text-sm leading-7 text-muted">{{ __('We review the context, identify the relevant expertise and respond using the details you provide.') }}</p>
                </div>
            </aside>
        </div>
    </section>
@endsection
