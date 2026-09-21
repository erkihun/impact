<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    @php($effectiveSettings = app(\App\Support\Settings\EffectiveSettings::class))
    @php($uiSettings = app(\App\Support\Settings\PublicUiSettings::class))
    @php($identity = $uiSettings->identity())
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>{{ __('Administration') }} - {{ $effectiveSettings->effective('site.name') }}</title>
    @if ($identity['favicon'])
        <link rel="icon" href="{{ url($identity['favicon']) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{ {{ $uiSettings->inlineCssVariables() }} }</style>
</head>
<body
    class="{{ implode(' ', $uiSettings->bodyClasses(admin: true)) }}"
    data-admin-sidebar-default="{{ $effectiveSettings->string('appearance.default_admin_sidebar_state') }}"
>
    <a href="#admin-main-content" class="skip-link">{{ __('Skip to content') }}</a>
    <div class="admin-shell" x-data="adminNavigation" x-bind:class="shellClass" x-on:keydown.escape.window="closeMobileOnEscape">
        @include('layouts.navigation')

        <div class="admin-main">
            @isset($header)
                <header class="admin-page-header">
                    <div>{{ $header }}</div>
                </header>
            @endisset

            @if (session('status'))
                <div class="admin-workspace pb-0" role="status" aria-live="polite">
                    <div class="status-success">{{ session('status') }}</div>
                </div>
            @endif

            <main id="admin-main-content" tabindex="-1">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
