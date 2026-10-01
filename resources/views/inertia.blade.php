@php
    $publicExperience = app(\App\Support\Settings\PublicUiSettings::class)->viewData();
    $identity = $publicExperience['identity'];
    $currentLocale = $publicExperience['localization']['current'];
    $alternateLocale = $publicExperience['localization']['alternate'];
    $alternateUrl = $alternateLocale
        ? (preg_replace('#/'.preg_quote($currentLocale, '#').'(?=/|$)#', '/'.$alternateLocale, url()->current(), 1)
            ?: route('localized-home', ['locale' => $alternateLocale]))
        : null;
    $meta = $page['props']['meta'] ?? [];
    $title = filled($meta['title'] ?? null)
        ? $meta['title'].' - '.$publicExperience['seo']['title_suffix']
        : $publicExperience['seo']['default_title'];
    $description = $meta['description'] ?? $publicExperience['seo']['default_description'];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description }}" head-key="description" inertia>
    <meta name="robots" content="{{ $meta['robots'] ?? $publicExperience['seo']['robots'] }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="{{ $publicExperience['seo']['twitter_card_type'] }}">
    @if ($identity['social_image'])
        <meta property="og:image" content="{{ url($identity['social_image']) }}">
    @endif
    @if ($identity['favicon'])
        <link rel="icon" href="{{ url($identity['favicon']) }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="{{ $currentLocale }}" href="{{ url()->current() }}">
    @if ($alternateLocale && $alternateUrl)
        <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternateUrl }}">
    @endif
    <link rel="alternate" hreflang="x-default" href="{{ route('localized-home', ['locale' => $publicExperience['localization']['default']]) }}">
    <title inertia>{{ $title }}</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/css/public-glass.css', 'resources/js/inertia.jsx'])
    <style>:root{ {{ $publicExperience['presentation']['css_variables'] }} }</style>
    @inertiaHead
</head>
<body
    class="public-glass {{ implode(' ', $publicExperience['presentation']['body_classes']) }}"
    data-default-theme="{{ $publicExperience['presentation']['default_theme'] }}"
    data-user-theme-enabled="{{ $publicExperience['presentation']['allow_user_theme'] ? 'true' : 'false' }}"
    data-date-format="{{ $publicExperience['localization']['date_format'] }}"
    data-time-format="{{ $publicExperience['localization']['time_format'] }}"
    data-first-day-of-week="{{ $publicExperience['localization']['first_day_of_week'] }}"
>
    {{-- Page data is a JSON data block (never executed, so the strict CSP allows it); unicode stays readable for crawlers. --}}
    <script data-page="app" type="application/json">{!! json_encode($page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <div id="app"></div>
    <noscript>
        <p style="padding:2rem;text-align:center">{{ __('This page needs JavaScript. You can still reach our services, insights and contact routes from the links below.') }}</p>
        <p style="text-align:center"><a href="{{ route('services.index', ['locale' => $currentLocale]) }}">{{ __('Services') }}</a> · <a href="{{ route('insights.index', ['locale' => $currentLocale]) }}">{{ __('Insights') }}</a> · <a href="{{ route('contact.create', ['locale' => $currentLocale]) }}">{{ __('Contact') }}</a></p>
    </noscript>
</body>
</html>
