@php
    $effectiveSettings = app(\App\Support\Settings\EffectiveSettings::class);
    $uiSettings = app(\App\Support\Settings\PublicUiSettings::class);
    $identity = $uiSettings->identity();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>{{ __('Secure access') }} - {{ $identity['name'] }}</title>
    @if ($identity['favicon'])
        <link rel="icon" href="{{ url($identity['favicon']) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{ {{ $uiSettings->inlineCssVariables() }} }</style>
</head>
<body class="{{ implode(' ', $uiSettings->bodyClasses()) }}">
    <a href="#auth-main-content" class="skip-link">{{ __('Skip to content') }}</a>
    <div class="auth-shell">
        <aside class="auth-brand-panel" aria-label="{{ $identity['name'] }}">
            <a href="{{ route('home') }}" class="flex items-center gap-4 text-white">
                @if ($identity['logo'])
                    <img class="h-12 w-auto max-w-48 object-contain" src="{{ $identity['logo'] }}" alt="{{ $identity['logo_alt'] }}">
                @else
                    <span class="brand-mark" aria-hidden="true">I</span>
                @endif
                <span>
                    <span class="block text-xl font-extrabold">{{ $identity['short_name'] }}</span>
                    <span class="block text-sm text-slate-300">{{ __('Secure staff access') }}</span>
                </span>
            </a>
            <div class="max-w-lg">
                <p class="eyebrow text-action-200">{{ __('Protected workspace') }}</p>
                <p class="mt-5 text-4xl font-extrabold leading-tight">{{ __('Manage trusted content and engagement safely.') }}</p>
                <p class="mt-5 text-base leading-8 text-slate-300">{{ __('Staff access is invitation-only, permission-aware and protected by multifactor authentication where required.') }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400">{{ __('Authorized users only. Activity may be audited.') }}</p>
                @if ($effectiveSettings->nullableString('security.login_notice'))
                    <p class="mt-3 text-sm text-slate-300">{{ $effectiveSettings->nullableString('security.login_notice') }}</p>
                @endif
            </div>
        </aside>

        <main id="auth-main-content" class="auth-form-panel" tabindex="-1">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-6 flex items-center gap-3 rounded-lg lg:hidden" aria-label="{{ __('Impact Consulting home') }}">
                    <span class="brand-mark" aria-hidden="true">I</span>
                    <span class="font-extrabold text-brand-900">{{ $identity['short_name'] }}</span>
                </a>
                <div class="auth-card">
                    {{ $slot }}
                </div>
                <p class="mt-6 text-center text-xs leading-5 text-muted">
                    {{ __('If you cannot access your account, use the approved recovery route or contact an administrator.') }}
                    @if ($effectiveSettings->nullableString('security.contact_email'))
                        <a class="text-link justify-center" href="mailto:{{ $effectiveSettings->nullableString('security.contact_email') }}">{{ $effectiveSettings->nullableString('security.contact_email') }}</a>
                    @endif
                </p>
            </div>
        </main>
    </div>
</body>
</html>
