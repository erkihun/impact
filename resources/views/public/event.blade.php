@extends('layouts.public')

@section('title', $event->title . ' — ' . __('Impact Consulting'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        __('Home') => route('localized-home', ['locale' => app()->getLocale()]),
        __('Events') => route('events.index', ['locale' => app()->getLocale()]),
        $event->title => null,
    ]" />
@endsection

@section('content')
    <article>
        @if ($managedComposition)
            <x-ui.page-composition :composition="$managedComposition" :heading-override="$event->title" :summary-override="$event->description" />
        @else
        <x-ui.page-header
            :eyebrow="__('Event')"
            :title="$event->title"
            :meta="$event->starts_at?->locale(app()->getLocale())->translatedFormat('d M Y, H:i') . ' · ' . $event->timezone . ' · ' . __(ucfirst($event->format))"
            variant="knowledge"
        />
        @endif

        <div class="public-section impact-editorial-surface">
        <div class="public-action-layout content-container">
            <div class="public-content-canvas prose prose-slate max-w-none prose-headings:font-editorial prose-headings:text-brand-950">
                <h2>{{ __('About this event') }}</h2>
                <p>{!! nl2br(e($event->description)) !!}</p>
                @if ($event->venue)
                    <h2>{{ __('Venue') }}</h2>
                    <p>{{ $event->venue }}</p>
                @endif
            </div>

            <aside class="task-panel" aria-labelledby="registration-heading">
                @if ($event->acceptsRegistrations())
                    <p class="eyebrow">{{ __('Attendance') }}</p>
                    <h2 id="registration-heading" class="heading-3 mt-3">{{ __('Register') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-muted">{{ __('Registration is confirmed only after the confirmation page displays a reference.') }}</p>

                    <form method="POST" action="{{ route('events.registrations.store', ['locale' => app()->getLocale(), 'slug' => $event->slug]) }}" class="mt-7 grid gap-5" data-prevent-duplicate>
                        @csrf
                        <div class="hidden" aria-hidden="true"><label>Website<input name="website" tabindex="-1"></label></div>
                        <x-ui.error-summary :errors="$errors" />
                        <div>
                            <label class="form-label" for="name">{{ __('Full name') }} <span class="form-required">({{ __('required') }})</span></label>
                            <input class="form-input" id="name" name="name" required value="{{ old('name') }}" autocomplete="name" @error('name') aria-invalid="true" @enderror>
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="email">{{ __('Email address') }} <span class="form-required">({{ __('required') }})</span></label>
                            <input class="form-input" id="email" name="email" type="email" required value="{{ old('email') }}" autocomplete="email" @error('email') aria-invalid="true" @enderror>
                            @error('email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <label class="flex items-start gap-3 text-sm leading-6">
                            <input id="privacy_acknowledged" class="mt-1 rounded" type="checkbox" name="privacy_acknowledged" value="1" required>
                            <span>{{ __('I agree to the use of my details to administer this registration.') }}</span>
                        </label>
                        @error('privacy_acknowledged')<p class="field-error">{{ $message }}</p>@enderror
                        <label class="flex items-start gap-3 text-sm leading-6">
                            <input class="mt-1 rounded" type="checkbox" name="marketing_consent" value="1">
                            <span>{{ __('Send me relevant future insights and events.') }}</span>
                        </label>
                        <button class="button-primary w-full" type="submit">{{ __('Confirm registration') }}</button>
                    </form>
                @else
                    <p class="status-badge status-badge-neutral">{{ __('Registration unavailable') }}</p>
                    <h2 id="registration-heading" class="heading-3 mt-4">{{ __('Registration closed') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-muted">{{ __('This event is full or no longer accepting registrations.') }}</p>
                    <a class="text-link mt-5" href="{{ route('events.index', ['locale' => app()->getLocale()]) }}">{{ __('View upcoming events') }} →</a>
                @endif
            </aside>
        </div>
        </div>
    </article>
@endsection
