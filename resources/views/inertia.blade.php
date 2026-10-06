@php
    $publicExperience = app(\App\Support\Settings\PublicUiSettings::class)->viewData();
    $identity = $publicExperience['identity'];
    $isPublic = str_starts_with($page['component'], 'Public/');
    $status = $page['props']['status'] ?? null;
    // Head elements are resolved and escaped by App\Services\Seo\SeoHeadRenderer.
    // With SSR they arrive through @inertiaHead instead.
    $seoHead = $page['props']['seo']['head'] ?? [];
    $ssr = app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch();
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (! $ssr)
        @foreach ($seoHead as $element)
    {!! $element !!}
        @endforeach
    @endif
    @if ($identity['favicon'])
        <link rel="icon" href="{{ url($identity['favicon']) }}">
    @endif
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/css/public-glass.css', 'resources/js/inertia.jsx'])
    <style>:root{ {{ $publicExperience['presentation']['css_variables'] }} }</style>
    @inertiaHead
</head>
<body
    class="{{ $isPublic ? 'public-glass' : '' }} {{ implode(' ', app(\App\Support\Settings\PublicUiSettings::class)->bodyClasses(admin: ! $isPublic)) }}"
    data-default-theme="{{ $publicExperience['presentation']['default_theme'] }}"
    data-user-theme-enabled="{{ $publicExperience['presentation']['allow_user_theme'] ? 'true' : 'false' }}"
    data-date-format="{{ $publicExperience['localization']['date_format'] }}"
    data-time-format="{{ $publicExperience['localization']['time_format'] }}"
    data-first-day-of-week="{{ $publicExperience['localization']['first_day_of_week'] }}"
>
    @if ($ssr)
        {!! $ssr->body !!}
    @else
    {{-- Inertia JSON data is inert; Unicode stays readable on CSR fallback. --}}
    <script data-page="app" type="application/json">{!! json_encode($page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <div id="app"></div>
    <noscript>
        @if ($page['component'] === 'Error' && $status === 404)
            <section style="padding:2rem;text-align:center">
                <h1>{{ __('Where to go next') }}</h1>
                <p>
                    <a href="{{ route('search') }}">{{ __('Search the site') }}</a> &middot;
                    <a href="{{ route('services.index') }}">{{ __('Browse services') }}</a> &middot;
                    <a href="{{ route('insights.index') }}">{{ __('Read insights') }}</a> &middot;
                    <a href="{{ route('contact.create') }}">{{ __('Contact us') }}</a>
                </p>
            </section>
        @endif
        <p style="padding:2rem;text-align:center">{{ __('This page needs JavaScript. You can still reach our services, insights and contact routes from the links below.') }}</p>
        <p style="text-align:center"><a href="{{ route('services.index') }}">{{ __('Services') }}</a> &middot; <a href="{{ route('insights.index') }}">{{ __('Insights') }}</a> &middot; <a href="{{ route('contact.create') }}">{{ __('Contact') }}</a></p>
    </noscript>
    @endif
</body>
</html>
