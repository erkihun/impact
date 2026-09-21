@extends('layouts.public')

@section('title', __('Contact — Impact Consulting'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[__('Home') => route('localized-home', ['locale' => app()->getLocale()]), __('Contact') => null]" />
@endsection

@section('content')
    @if ($managedComposition)
        <x-ui.page-composition :composition="$managedComposition" />
    @else
    <x-ui.page-header
        :eyebrow="__('Contact')"
        :title="__('Choose the right route for your request.')"
        :description="__('Use this form for general, partnership or media inquiries. Consultation and proposal requests have dedicated secure pathways.')"
    />
    @endif

    <section class="public-section impact-editorial-surface">
        <div class="public-engagement-layout content-container">
        <form method="POST" action="{{ route('contact.store', ['locale' => app()->getLocale()]) }}" class="engagement-form-canvas" data-prevent-duplicate>
            @csrf
            <input type="hidden" name="policy_version" value="{{ $publicExperience['privacy']['policy_version'] }}">
            <div class="hidden" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>

            <x-ui.error-summary class="mb-8" :errors="$errors" />

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="form-label" for="type">{{ __('Request type') }} *</label>
                    <select class="form-input" id="type" name="type" required>
                        <option value="contact" @selected(old('type') === 'contact')>{{ __('General contact') }}</option>
                        <option value="partnership" @selected(old('type') === 'partnership')>{{ __('Partnership inquiry') }}</option>
                        <option value="media" @selected(old('type') === 'media')>{{ __('Media inquiry') }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="contact_name">{{ __('Full name') }} *</label>
                    <input class="form-input" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required maxlength="160" autocomplete="name">
                </div>
                <div>
                    <label class="form-label" for="email">{{ __('Work email') }} *</label>
                    <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="organization_name">{{ __('Organization') }}</label>
                    <input class="form-input" id="organization_name" name="organization_name" value="{{ old('organization_name') }}" maxlength="200" autocomplete="organization">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="description">{{ __('Message') }} *</label>
                    <textarea class="form-input min-h-40" id="description" name="description" required minlength="20" maxlength="10000">{{ old('description') }}</textarea>
                    <p class="mt-2 text-xs text-slate-500">{{ __('Do not include sensitive personal or confidential information at this stage.') }}</p>
                </div>
            </div>

            <label class="mt-7 flex items-start gap-3 text-sm leading-6 text-slate-700">
                <input id="privacy_acknowledged" class="mt-1 rounded border-slate-300 text-impact-700 focus:ring-impact-600" type="checkbox" name="privacy_acknowledged" value="1" required>
                <span>{{ __('I understand that Impact Consulting will use these details to assess and respond to this request under the current privacy notice.') }}</span>
            </label>
            <button class="button-primary mt-8" type="submit">{{ __('Submit request') }} <span aria-hidden="true">→</span></button>
        </form>

        <aside class="form-context-panel" aria-labelledby="contact-routes-title">
            <x-ui.insight-marker :label="__('Contact')" />
            <h2 id="contact-routes-title" class="mt-5 font-editorial text-2xl font-bold text-white">{{ __('Choose the right route for your request.') }}</h2>
            <p class="mt-4 text-sm leading-7 text-slate-300">{{ __('Use this form for general, partnership or media inquiries. Consultation and proposal requests have dedicated secure pathways.') }}</p>
            <div class="mt-7 grid gap-3 border-t border-white/20 pt-6">
                @if ($publicExperience['features']['consultation'])
                    <a class="button-light w-full" href="{{ route('consultation.create', ['locale' => app()->getLocale()]) }}">{{ __('Request a consultation') }}</a>
                @endif
                @if ($publicExperience['features']['rfp'])
                    <a class="button-secondary-dark w-full" href="{{ route('rfp.create', ['locale' => app()->getLocale()]) }}">{{ __('Submit an RFP') }}</a>
                @endif
            </div>
        </aside>
        </div>
    </section>
@endsection
