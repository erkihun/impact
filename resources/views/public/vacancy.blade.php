@extends('layouts.public')

@section('title', $vacancy->title . ' — ' . __('Impact Consulting'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        __('Home') => route('localized-home', ['locale' => app()->getLocale()]),
        __('Careers') => route('careers.index', ['locale' => app()->getLocale()]),
        $vacancy->title => null,
    ]" />
@endsection

@section('content')
    <article>
        @if ($managedComposition)
            <x-ui.page-composition :composition="$managedComposition" :heading-override="$vacancy->title" :summary-override="$vacancy->description" />
        @else
        <x-ui.page-header
            :eyebrow="__('Career opportunity') . ' · ' . $vacancy->reference_no"
            :title="$vacancy->title"
            :meta="$vacancy->location . ' · ' . __(ucfirst($vacancy->type))"
            variant="paper"
        />
        @endif

        <div class="public-section impact-editorial-surface">
        <div class="public-action-layout content-container">
            <div class="public-content-canvas prose prose-slate max-w-none prose-headings:font-editorial prose-headings:text-brand-950">
                <h2>{{ __('The opportunity') }}</h2>
                <p>{!! nl2br(e($vacancy->description)) !!}</p>
                <h2>{{ __('Requirements') }}</h2>
                <p>{!! nl2br(e($vacancy->requirements)) !!}</p>
                @if ($vacancy->closes_at)
                    <p>
                        <strong>{{ __('Closing date') }}:</strong>
                        <time datetime="{{ $vacancy->closes_at->toDateString() }}">
                            {{ $vacancy->closes_at->locale(app()->getLocale())->translatedFormat('d M Y') }}
                        </time>
                    </p>
                @endif
            </div>

            <aside class="task-panel task-panel-brand" aria-labelledby="application-heading">
                @if ($vacancy->acceptsApplications())
                    <p class="eyebrow">{{ __('Secure application') }}</p>
                    <h2 id="application-heading" class="heading-3 mt-3">{{ __('Apply') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-muted">{{ __('An application is received only when the confirmation page displays a reference.') }}</p>

                    <form method="POST" enctype="multipart/form-data" action="{{ route('careers.applications.store', ['locale' => app()->getLocale(), 'slug' => $vacancy->slug]) }}" class="mt-7 grid gap-5" data-prevent-duplicate>
                        @csrf
                        <div class="hidden" aria-hidden="true"><label>Website<input name="website" tabindex="-1"></label></div>
                        <x-ui.error-summary :errors="$errors" />

                        <div>
                            <label class="form-label" for="applicant_name">{{ __('Full name') }} <span class="form-required">({{ __('required') }})</span></label>
                            <input class="form-input" id="applicant_name" name="applicant_name" required value="{{ old('applicant_name') }}" autocomplete="name" @error('applicant_name') aria-invalid="true" @enderror>
                            @error('applicant_name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="email">{{ __('Email address') }} <span class="form-required">({{ __('required') }})</span></label>
                            <input class="form-input" id="email" name="email" type="email" required value="{{ old('email') }}" autocomplete="email" @error('email') aria-invalid="true" @enderror>
                            @error('email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="phone">{{ __('Phone') }}</label>
                            <input class="form-input" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel">
                        </div>

                        <x-ui.file-upload
                            id="cv"
                            name="cv"
                            :label="__('CV (PDF or DOCX, max 10 MB)')"
                            accept=".pdf,.docx"
                            :help="__('The file remains unavailable to staff until security processing is complete.')"
                            required
                        />

                        <div>
                            <label class="form-label" for="cover_letter">{{ __('Cover letter') }}</label>
                            <textarea class="form-input min-h-32" id="cover_letter" name="cover_letter" maxlength="10000">{{ old('cover_letter') }}</textarea>
                        </div>
                        <label class="flex items-start gap-3 text-sm leading-6">
                            <input id="privacy_acknowledged" class="mt-1 rounded" type="checkbox" name="privacy_acknowledged" value="1" required>
                            <span>{{ __('I agree to the processing of my application under the recruitment privacy notice.') }}</span>
                        </label>
                        @error('privacy_acknowledged')<p class="field-error">{{ $message }}</p>@enderror
                        <button class="button-primary w-full" type="submit">{{ __('Submit application') }}</button>
                    </form>
                @else
                    <p class="status-badge status-badge-neutral">{{ __('Application unavailable') }}</p>
                    <h2 id="application-heading" class="heading-3 mt-4">{{ __('Applications closed') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-muted">{{ __('This opportunity is no longer accepting applications.') }}</p>
                    <a class="text-link mt-5" href="{{ route('careers.index', ['locale' => app()->getLocale()]) }}">{{ __('View current opportunities') }} →</a>
                @endif
            </aside>
        </div>
        </div>
    </article>
@endsection
