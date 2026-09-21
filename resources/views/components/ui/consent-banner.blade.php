@props(['policyVersion'])

@php
    $locale = app()->getLocale();
    $categories = [
        'preferences' => [
            'label' => __('Preferences'),
            'description' => __('Remembers choices such as your language and display settings so you do not have to set them again.'),
        ],
        'analytics' => [
            'label' => __('Analytics'),
            'description' => __('Helps us understand which services and insights are useful. We do not record form text or uploaded documents.'),
        ],
        'marketing' => [
            'label' => __('Marketing'),
            'description' => __('Measures whether our campaigns and newsletters reach the right audiences.'),
        ],
    ];
@endphp

<div
    x-data="consentManager"
    data-policy-version="{{ $policyVersion }}"
    data-endpoint="{{ route('consent.update') }}"
    data-label-allowed="{{ __('Allowed') }}"
    data-label-not-allowed="{{ __('Not allowed') }}"
    x-on:keydown.escape.window="hideDetails"
    x-on:open-consent-preferences.window="reopen"
>
    {{-- First layer: plain-language summary with equivalent accept, reject and manage choices. --}}
    <section
        x-cloak
        x-show="bannerVisible"
        x-ref="banner"
        tabindex="-1"
        class="consent-banner"
        role="region"
        aria-labelledby="consent-banner-title"
    >
        <div class="content-container py-5">
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                <div>
                    <h2 id="consent-banner-title" class="text-base font-bold text-brand-950">{{ __('Your choices about cookies and storage') }}</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-muted">
                        {{ __('We use necessary storage to keep this site secure and working. Optional storage for preferences, analytics and marketing is used only if you agree. You can change your choice at any time.') }}
                        <a class="font-semibold text-action-700 underline underline-offset-4 hover:text-action-900" href="{{ route('legal.privacy', ['locale' => $locale]) }}">{{ __('Read the privacy notice') }}</a>
                    </p>
                    <p x-cloak x-show="failed" class="mt-3 text-sm font-medium text-danger" role="alert">
                        {{ __('Your choice was not saved. Check your connection and try again.') }}
                    </p>
                </div>
                {{-- Equal visual weight: no option is styled to be more attractive than another. --}}
                <div class="flex flex-col gap-3 sm:flex-row lg:shrink-0">
                    <button type="button" class="button-secondary" x-on:click="showDetails" x-bind:disabled="saving">{{ __('Manage choices') }}</button>
                    <button type="button" class="button-secondary" x-on:click="rejectAll" x-bind:disabled="saving">{{ __('Reject optional') }}</button>
                    <button type="button" class="button-secondary" x-on:click="acceptAll" x-bind:disabled="saving">{{ __('Accept optional') }}</button>
                </div>
            </div>
        </div>
    </section>

    {{-- Second layer: the preference centre, grouped by purpose. --}}
    <div x-cloak x-show="detailsOpen" class="consent-overlay" role="presentation">
        <div
            class="consent-dialog"
            x-ref="details"
            x-on:keydown="trapConsent"
            role="dialog"
            aria-modal="true"
            aria-labelledby="consent-details-title"
        >
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-6">
                <div>
                    <h2 id="consent-details-title" x-ref="detailsHeading" tabindex="-1" class="text-xl font-extrabold text-brand-950">{{ __('Manage your privacy choices') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-muted">{{ __('Choose which optional storage you allow. Necessary storage cannot be switched off because the site will not work without it.') }}</p>
                </div>
                <button type="button" class="icon-button" x-on:click="hideDetails" aria-label="{{ __('Close privacy choices') }}">
                    <svg aria-hidden="true" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <div class="grid gap-4 p-6">
                <div class="rounded-xl border border-slate-200 bg-quiet p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <h3 class="text-sm font-bold text-brand-950">{{ __('Necessary') }}</h3>
                        <span class="status-badge status-badge-success">{{ __('Always active') }}</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-muted">{{ __('Keeps your session secure, protects forms against misuse and remembers your privacy choice.') }}</p>
                </div>

                @foreach ($categories as $key => $category)
                    <div class="rounded-xl border border-slate-200 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <h3 class="text-sm font-bold text-brand-950">{{ $category['label'] }}</h3>
                            <label class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-brand-900" for="consent-{{ $key }}">
                                <input
                                    id="consent-{{ $key }}"
                                    type="checkbox"
                                    class="size-5 rounded border-slate-400 text-action-500 focus:ring-knowledge-600"
                                    x-bind:checked="{{ $key }}Allowed"
                                    x-on:change="toggle{{ ucfirst($key) }}"
                                    aria-describedby="consent-{{ $key }}-description"
                                >
                                <span x-text="{{ $key }}Label"></span>
                            </label>
                        </div>
                        <p id="consent-{{ $key }}-description" class="mt-2 text-sm leading-6 text-muted">{{ $category['description'] }}</p>
                    </div>
                @endforeach

                <p x-cloak x-show="failed" class="text-sm font-medium text-danger" role="alert">
                    {{ __('Your choice was not saved. Check your connection and try again.') }}
                </p>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 p-6 sm:flex-row sm:justify-end">
                <button type="button" class="button-secondary" x-on:click="rejectAll" x-bind:disabled="saving">{{ __('Reject optional') }}</button>
                <button type="button" class="button-secondary" x-on:click="acceptAll" x-bind:disabled="saving">{{ __('Accept optional') }}</button>
                <button type="button" class="button-primary" x-on:click="saveSelection" x-bind:disabled="saving">
                    <span x-show="idle">{{ __('Save my choices') }}</span>
                    <span x-cloak x-show="saving">{{ __('Saving…') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
