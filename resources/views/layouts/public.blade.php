@php
    $identity = $publicExperience['identity'];
    $currentLocale = $publicExperience['localization']['current'];
    $alternateLocale = $publicExperience['localization']['alternate'];
    $alternateUrl = preg_replace(
        '#/'.preg_quote($currentLocale, '#').'(?=/|$)#',
        '/'.$alternateLocale,
        url()->current(),
        1,
    ) ?: ($alternateLocale ? route('localized-home', ['locale' => $alternateLocale]) : null);
    $publicBrandName = $identity['short_name'];
    $publicBrandFull = $identity['name'];
    $publicBrandTagline = $identity['tagline'];
    $copyrightOwner = $identity['copyright_owner'];
    $searchEnabled = $publicExperience['features']['search'];
    $consultationEnabled = $publicExperience['features']['consultation'];
    $rfpEnabled = $publicExperience['features']['rfp'];
    $contactEnabled = $publicExperience['features']['contact'];
    $managedPrimaryLabels = $managedNavigation['primary']->pluck('label', 'route');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', $publicExperience['seo']['default_description'])">
    <meta name="robots" content="@yield('robots', $publicExperience['seo']['robots'])">
    <meta property="og:title" content="@yield('title', $publicExperience['seo']['default_title'])">
    <meta property="og:description" content="@yield('meta_description', $publicExperience['seo']['default_description'])">
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
    <title>@hasSection('title')@yield('title') - {{ $publicExperience['seo']['title_suffix'] }}@else{{ $publicExperience['seo']['default_title'] }}@endif</title>
    @stack('structured_data')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{ {{ $publicExperience['presentation']['css_variables'] }} }</style>
</head>
<body
    class="{{ implode(' ', $publicExperience['presentation']['body_classes']) }}"
    data-default-theme="{{ $publicExperience['presentation']['default_theme'] }}"
    data-user-theme-enabled="{{ $publicExperience['presentation']['allow_user_theme'] ? 'true' : 'false' }}"
    data-date-format="{{ $publicExperience['localization']['date_format'] }}"
    data-time-format="{{ $publicExperience['localization']['time_format'] }}"
    data-first-day-of-week="{{ $publicExperience['localization']['first_day_of_week'] }}"
>
    <a href="#main-content" class="skip-link">{{ __('Skip to content') }}</a>

    @if ($publicExperience['maintenance']['banner_enabled'])
        <div class="status-warning rounded-none border-x-0" role="status">
            <div class="content-container flex flex-wrap items-center justify-between gap-3">
                <p>{{ $publicExperience['maintenance']['banner_message'] }}</p>
                @if ($publicExperience['maintenance']['status_page_enabled'])
                    <a class="text-link" href="{{ route('system-status') }}">{{ __('View system status') }}</a>
                @endif
            </div>
        </div>
    @endif

    <header
        class="public-header"
        x-data="publicNavigation"
        x-on:keydown.escape.window="handleEscape"
    >
        <div class="bg-brand-950 text-white">
            <div class="content-container flex min-h-10 items-center justify-between gap-4 py-2 text-xs">
                <p class="hidden text-slate-200 sm:block">{{ __('Strategy, evidence and implementation for lasting change') }}</p>
                @if ($consultationEnabled)
                <a href="{{ route('consultation.create', ['locale' => $currentLocale]) }}" class="ms-auto inline-flex min-h-9 items-center font-bold text-action-100 hover:text-white">
                    {{ __('Request a consultation') }} <span aria-hidden="true">→</span>
                </a>
                @endif
            </div>
        </div>

        <div class="public-header-bar content-container flex min-h-20 items-center justify-between gap-5">
            <a href="{{ route('localized-home', ['locale' => $currentLocale]) }}" class="flex min-h-11 items-center gap-3 rounded-lg" aria-label="{{ __(':name home', ['name' => $publicBrandFull]) }}">
                @if ($identity['logo'])
                    <img class="h-11 w-auto max-w-48 object-contain" src="{{ $identity['logo'] }}" alt="{{ $identity['logo_alt'] }}">
                @else
                    <span class="brand-mark" aria-hidden="true">{{ mb_substr((string) $identity['abbreviation'], 0, 1) ?: 'I' }}</span>
                @endif
                <span>
                    <span class="block text-lg font-extrabold leading-none text-brand-900">{{ $publicBrandName }}</span>
                    <span class="mt-1 block text-[0.65rem] font-bold uppercase tracking-[0.2em] text-muted">Consulting</span>
                </span>
            </a>

            <nav aria-label="{{ __('Primary navigation') }}" class="primary-navigation" x-on:click.outside="closeMenu">
                <div class="flex">
                    <a
                        class="nav-link"
                        href="{{ route('localized-home', ['locale' => $currentLocale]) }}"
                        data-nav-item="home"
                        @if (request()->routeIs('home', 'localized-home')) aria-current="page" @endif
                    >
                        <x-ui.icon name="home" class="nav-menu-icon" />
                        {{ $managedPrimaryLabels['localized-home'] ?? __('Home') }}
                    </a>
                    <button
                        class="mega-trigger"
                        type="button"
                        data-menu="about"
                        x-on:click="toggleMenu"
                        aria-controls="mega-about"
                        x-bind:aria-expanded="aboutExpanded"
                        @if (request()->routeIs('about.*')) aria-current="page" @endif
                    >
                        <x-ui.icon name="about" class="nav-menu-icon" />
                        {{ $managedPrimaryLabels['about.show'] ?? __('About') }}
                        <span class="nav-chevron" aria-hidden="true">
                            <svg viewBox="0 0 12 12" fill="none"><path d="m3 4.5 3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </button>
                    <div id="mega-about" class="mega-panel" x-cloak x-show="aboutExpanded" x-transition.opacity>
                        <div class="content-container grid gap-8 py-8 lg:grid-cols-[1fr_2fr_1fr]">
                            <div>
                                <p class="eyebrow">{{ __('About Impact Consulting') }}</p>
                                <p class="mt-3 text-sm leading-6 text-muted">{{ __('Understand our purpose, working approach and institutional commitments.') }}</p>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <a class="mega-link" href="{{ route('about.show', ['locale' => $currentLocale]) }}">
                                    <span class="mega-link-icon"><x-ui.icon name="about" /></span>
                                    <span><strong class="block text-brand-900">{{ __('Who we are') }}</strong><span class="mt-1 block text-sm text-muted">{{ __('Purpose, values and the way we work') }}</span></span>
                                </a>
                                <a class="mega-link" href="{{ route('experts.index', ['locale' => $currentLocale]) }}">
                                    <span class="mega-link-icon"><x-ui.icon name="experts" /></span>
                                    <span><strong class="block text-brand-900">{{ __('Our experts') }}</strong><span class="mt-1 block text-sm text-muted">{{ __('Experience, credentials and selected work') }}</span></span>
                                </a>
                                <a class="mega-link" href="{{ route('careers.index', ['locale' => $currentLocale]) }}">
                                    <span class="mega-link-icon"><x-ui.icon name="careers" /></span>
                                    <span><strong class="block text-brand-900">{{ __('Careers') }}</strong><span class="mt-1 block text-sm text-muted">{{ __('Current opportunities and application routes') }}</span></span>
                                </a>
                                @if ($contactEnabled)
                                    <a class="mega-link" href="{{ route('contact.create', ['locale' => $currentLocale]) }}">
                                        <span class="mega-link-icon"><x-ui.icon name="contact" /></span>
                                        <span><strong class="block text-brand-900">{{ __('Contact') }}</strong><span class="mt-1 block text-sm text-muted">{{ __('Find the right route for your request') }}</span></span>
                                    </a>
                                @endif
                            </div>
                            <div class="rounded-xl bg-brand-950 p-5 text-white">
                                <p class="text-sm font-bold">{{ __('Have a specific assignment?') }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">{{ __('Share a structured brief through our secure proposal route.') }}</p>
                                @if ($rfpEnabled)<a class="mt-4 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-action-100 hover:text-white" href="{{ route('rfp.create', ['locale' => $currentLocale]) }}"><x-ui.icon name="rfp" class="size-4" />{{ __('Submit an RFP') }} →</a>@endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex">
                    <button
                        class="mega-trigger"
                        type="button"
                        data-menu="services"
                        x-on:click="toggleMenu"
                        aria-controls="mega-services"
                        x-bind:aria-expanded="servicesExpanded"
                        @if (request()->routeIs('services.*')) aria-current="page" @endif
                    >
                        <x-ui.icon name="services" class="nav-menu-icon" />
                        {{ $managedPrimaryLabels['services.index'] ?? __('Services') }}
                        <span class="nav-chevron" aria-hidden="true">
                            <svg viewBox="0 0 12 12" fill="none"><path d="m3 4.5 3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </button>
                    <div id="mega-services" class="mega-panel" x-cloak x-show="servicesExpanded" x-transition.opacity>
                        <div class="content-container grid gap-8 py-8 lg:grid-cols-[1fr_2fr_1fr]">
                            <div>
                                <p class="eyebrow">{{ __('Consulting services') }}</p>
                                <p class="mt-3 text-sm leading-6 text-muted">{{ __('Focused advice that connects diagnosis, strategy, delivery and learning.') }}</p>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <a class="mega-link" href="{{ route('services.index', ['locale' => $currentLocale]) }}">
                                    <span class="mega-link-icon"><x-ui.icon name="services" /></span>
                                    <span><strong class="block text-brand-900">{{ __('Explore all services') }}</strong><span class="mt-1 block text-sm text-muted">{{ __('Browse current published capabilities') }}</span></span>
                                </a>
                                <a class="mega-link" href="{{ route('case-studies.index', ['locale' => $currentLocale]) }}">
                                    <span class="mega-link-icon"><x-ui.icon name="case-studies" /></span>
                                    <span><strong class="block text-brand-900">{{ __('Evidence and outcomes') }}</strong><span class="mt-1 block text-sm text-muted">{{ __('See related work and measurable results') }}</span></span>
                                </a>
                            </div>
                            <div class="rounded-xl bg-action-50 p-5">
                                <p class="text-sm font-bold text-brand-900">{{ __('Not sure which service fits?') }}</p>
                                <p class="mt-2 text-sm leading-6 text-muted">{{ __('Describe the outcome you need and we will route your request.') }}</p>
                                @if ($consultationEnabled)<a class="text-link mt-3 gap-2" href="{{ route('consultation.create', ['locale' => $currentLocale]) }}"><x-ui.icon name="consultation" class="size-4" />{{ __('Request a consultation') }} →</a>@endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex">
                    <button
                        class="mega-trigger"
                        type="button"
                        data-menu="industries"
                        x-on:click="toggleMenu"
                        aria-controls="mega-industries"
                        x-bind:aria-expanded="industriesExpanded"
                        @if (request()->routeIs('industries.*')) aria-current="page" @endif
                    >
                        <x-ui.icon name="industries" class="nav-menu-icon" />
                        {{ $managedPrimaryLabels['industries.index'] ?? __('Industries') }}
                        <span class="nav-chevron" aria-hidden="true">
                            <svg viewBox="0 0 12 12" fill="none"><path d="m3 4.5 3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </button>
                    <div id="mega-industries" class="mega-panel" x-cloak x-show="industriesExpanded" x-transition.opacity>
                        <div class="content-container grid gap-8 py-8 lg:grid-cols-[1fr_2fr]">
                            <div>
                                <p class="eyebrow">{{ __('Sector insight') }}</p>
                                <p class="mt-3 text-sm leading-6 text-muted">{{ __('Explore sector context, relevant services and approved evidence.') }}</p>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <a class="mega-link" href="{{ route('industries.index', ['locale' => $currentLocale]) }}">
                                    <span class="mega-link-icon"><x-ui.icon name="industries" /></span>
                                    <span><strong class="block text-brand-900">{{ __('Browse industries') }}</strong><span class="mt-1 block text-sm text-muted">{{ __('Find advice grounded in your operating context') }}</span></span>
                                </a>
                                <a class="mega-link" href="{{ route('case-studies.index', ['locale' => $currentLocale]) }}">
                                    <span class="mega-link-icon"><x-ui.icon name="case-studies" /></span>
                                    <span><strong class="block text-brand-900">{{ __('Related case studies') }}</strong><span class="mt-1 block text-sm text-muted">{{ __('Review authorized examples and outcomes') }}</span></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <a class="nav-link" href="{{ route('experts.index', ['locale' => $currentLocale]) }}" @if (request()->routeIs('experts.*')) aria-current="page" @endif>
                    <x-ui.icon name="experts" class="nav-menu-icon" />
                    {{ $managedPrimaryLabels['experts.index'] ?? __('Experts') }}
                </a>
                <a class="nav-link" href="{{ route('case-studies.index', ['locale' => $currentLocale]) }}" @if (request()->routeIs('case-studies.*')) aria-current="page" @endif>
                    <x-ui.icon name="case-studies" class="nav-menu-icon" />
                    {{ $managedPrimaryLabels['case-studies.index'] ?? __('Case studies') }}
                </a>

                <div class="flex">
                    <button
                        class="mega-trigger"
                        type="button"
                        data-menu="insights"
                        x-on:click="toggleMenu"
                        aria-controls="mega-insights"
                        x-bind:aria-expanded="insightsExpanded"
                        @if (request()->routeIs('insights.*', 'events.*')) aria-current="page" @endif
                    >
                        <x-ui.icon name="insights" class="nav-menu-icon" />
                        {{ $managedPrimaryLabels['insights.index'] ?? __('Insights') }}
                        <span class="nav-chevron" aria-hidden="true">
                            <svg viewBox="0 0 12 12" fill="none"><path d="m3 4.5 3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </button>
                    <div id="mega-insights" class="mega-panel" x-cloak x-show="insightsExpanded" x-transition.opacity>
                        <div class="content-container grid gap-8 py-8 lg:grid-cols-[1fr_2fr]">
                            <div>
                                <p class="eyebrow">{{ __('Knowledge centre') }}</p>
                                <p class="mt-3 text-sm leading-6 text-muted">{{ __('Evidence, analysis and practical learning from our current published work.') }}</p>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <a class="mega-link" href="{{ route('insights.index', ['locale' => $currentLocale]) }}">
                                    <span class="mega-link-icon"><x-ui.icon name="insights" /></span>
                                    <span><strong class="block text-brand-900">{{ __('Articles and reports') }}</strong><span class="mt-1 block text-sm text-muted">{{ __('Browse published knowledge content') }}</span></span>
                                </a>
                                <a class="mega-link" href="{{ route('events.index', ['locale' => $currentLocale]) }}">
                                    <span class="mega-link-icon"><x-ui.icon name="events" /></span>
                                    <span><strong class="block text-brand-900">{{ __('Events') }}</strong><span class="mt-1 block text-sm text-muted">{{ __('Upcoming learning and registration opportunities') }}</span></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="flex items-center gap-1">
                @if ($searchEnabled)
                    <a href="{{ route('search', ['locale' => $currentLocale]) }}" class="icon-link" aria-label="{{ __('Search') }}">
                        <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    </a>
                @endif
                @if ($alternateLocale && $alternateUrl)
                    <a href="{{ $alternateUrl }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-3 text-xs font-bold uppercase hover:border-action-500 hover:text-action-700" hreflang="{{ $alternateLocale }}" lang="{{ $alternateLocale }}">
                        {{ $alternateLocale }}
                    </a>
                @endif
                @if ($consultationEnabled)
                    <a class="button-primary ms-1 hidden 2xl:inline-flex" href="{{ route('consultation.create', ['locale' => $currentLocale]) }}">
                        <x-ui.icon name="consultation" class="size-4" />
                        {{ __('Consultation') }}
                    </a>
                @endif
                <button class="icon-button 2xl:hidden" type="button" x-on:click="openMobile" aria-controls="mobile-navigation" x-bind:aria-expanded="mobileOpen" aria-label="{{ __('Open menu') }}">
                    <svg aria-hidden="true" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <div
            id="mobile-navigation"
            class="mobile-sheet 2xl:hidden"
            x-cloak
            x-show="mobileOpen"
            x-transition.opacity
            x-ref="mobileSheet"
            x-on:keydown="trapFocus"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mobile-navigation-title"
        >
            <div class="content-container flex min-h-20 items-center justify-between border-b border-slate-200">
                <p id="mobile-navigation-title" class="font-bold text-brand-900">{{ __('Navigation') }}</p>
                <button class="icon-button" type="button" x-ref="mobileClose" x-on:click="closeMobile" aria-label="{{ __('Close menu') }}">
                    <svg aria-hidden="true" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="content-container grid gap-6 py-6">
                <div class="grid grid-cols-2 gap-3">
                    @if ($searchEnabled)<a class="button-secondary" href="{{ route('search', ['locale' => $currentLocale]) }}"><x-ui.icon name="search" class="size-4" />{{ __('Search') }}</a>@endif
                    @if ($alternateLocale && $alternateUrl)<a class="button-secondary" href="{{ $alternateUrl }}" hreflang="{{ $alternateLocale }}"><x-ui.icon name="language" class="size-4" />{{ strtoupper($alternateLocale) }}</a>@endif
                </div>
                @if ($consultationEnabled)<a class="button-primary w-full" href="{{ route('consultation.create', ['locale' => $currentLocale]) }}"><x-ui.icon name="consultation" />{{ __('Request a consultation') }}</a>@endif
                <nav aria-label="{{ __('Mobile navigation') }}" class="grid gap-2">
                    <a class="mobile-link" href="{{ route('localized-home', ['locale' => $currentLocale]) }}" data-nav-item="home"><x-ui.icon name="home" class="mobile-menu-icon" />{{ $managedPrimaryLabels['localized-home'] ?? __('Home') }}</a>
                    <a class="mobile-link" href="{{ route('about.show', ['locale' => $currentLocale]) }}"><x-ui.icon name="about" class="mobile-menu-icon" />{{ $managedPrimaryLabels['about.show'] ?? __('About') }}</a>
                    <a class="mobile-link" href="{{ route('services.index', ['locale' => $currentLocale]) }}"><x-ui.icon name="services" class="mobile-menu-icon" />{{ $managedPrimaryLabels['services.index'] ?? __('Services') }}</a>
                    <a class="mobile-link" href="{{ route('industries.index', ['locale' => $currentLocale]) }}"><x-ui.icon name="industries" class="mobile-menu-icon" />{{ $managedPrimaryLabels['industries.index'] ?? __('Industries') }}</a>
                    <a class="mobile-link" href="{{ route('experts.index', ['locale' => $currentLocale]) }}"><x-ui.icon name="experts" class="mobile-menu-icon" />{{ $managedPrimaryLabels['experts.index'] ?? __('Experts') }}</a>
                    <a class="mobile-link" href="{{ route('case-studies.index', ['locale' => $currentLocale]) }}"><x-ui.icon name="case-studies" class="mobile-menu-icon" />{{ $managedPrimaryLabels['case-studies.index'] ?? __('Case studies') }}</a>
                    <a class="mobile-link" href="{{ route('insights.index', ['locale' => $currentLocale]) }}"><x-ui.icon name="insights" class="mobile-menu-icon" />{{ $managedPrimaryLabels['insights.index'] ?? __('Insights') }}</a>
                    <a class="mobile-link" href="{{ route('events.index', ['locale' => $currentLocale]) }}"><x-ui.icon name="events" class="mobile-menu-icon" />{{ __('Events') }}</a>
                    <a class="mobile-link" href="{{ route('careers.index', ['locale' => $currentLocale]) }}"><x-ui.icon name="careers" class="mobile-menu-icon" />{{ __('Careers') }}</a>
                    @if ($contactEnabled)<a class="mobile-link" href="{{ route('contact.create', ['locale' => $currentLocale]) }}"><x-ui.icon name="contact" class="mobile-menu-icon" />{{ __('Contact') }}</a>@endif
                </nav>
            </div>
        </div>
    </header>

    @if (session('status') || session('submission'))
        <div class="content-container pt-5" role="status" aria-live="polite">
            <div class="status-success">
                {{ session('status') ?? data_get(session('submission'), 'message') }}
                @if (data_get(session('submission'), 'reference'))
                    <strong class="ms-2">{{ __('Reference') }}: {{ data_get(session('submission'), 'reference') }}</strong>
                @endif
            </div>
        </div>
    @endif

    <main id="main-content" class="public-main" tabindex="-1">
        @hasSection('breadcrumbs')
            <div class="breadcrumb-band">
                <div class="content-container py-4">
                    @yield('breadcrumbs')
                </div>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="public-footer border-t border-white/10 text-slate-200">
        <div class="content-container grid gap-12 py-16 lg:grid-cols-[1.2fr_.8fr_.8fr_1.2fr]">
            <div>
                <p class="text-2xl font-extrabold text-white">{{ $identity['legal_name'] }}</p>
                <p class="mt-4 max-w-sm text-sm leading-7 text-slate-300">{{ $publicBrandTagline }}</p>
                <address class="mt-4 grid gap-1 text-sm not-italic text-slate-300">
                    @if ($identity['address'])<span>{{ $identity['address'] }}</span>@endif
                    @if ($identity['postal_address'])<span>{{ $identity['postal_address'] }}</span>@endif
                    @if ($identity['phone'])<a class="hover:text-white hover:underline" href="tel:{{ $identity['phone'] }}">{{ $identity['phone'] }}</a>@endif
                    @if ($identity['email'])<a class="hover:text-white hover:underline" href="mailto:{{ $identity['email'] }}">{{ $identity['email'] }}</a>@endif
                    @if ($identity['working_hours'])<span>{{ $identity['working_hours'] }}</span>@endif
                </address>
                @if ($consultationEnabled)<a class="mt-5 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-action-100 hover:text-white" href="{{ route('consultation.create', ['locale' => $currentLocale]) }}"><x-ui.icon name="consultation" class="size-4" />{{ __('Request a consultation') }} →</a>@endif
            </div>
            <nav aria-labelledby="footer-explore-heading">
                <h2 id="footer-explore-heading" class="footer-heading">{{ __('Explore') }}</h2>
                <div class="mt-4 grid gap-1 text-sm">
                    @foreach ($managedNavigation['footer_explore'] as $item)
                        <a class="footer-menu-link" href="{{ $item['url'] }}"><x-ui.icon :name="$item['icon']" class="footer-menu-icon" />{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </nav>
            <nav aria-labelledby="footer-engage-heading">
                <h2 id="footer-engage-heading" class="footer-heading">{{ __('Engage') }}</h2>
                <div class="mt-4 grid gap-1 text-sm">
                    @foreach ($managedNavigation['footer_engage'] as $item)
                        <a class="footer-menu-link" href="{{ $item['url'] }}"><x-ui.icon :name="$item['icon']" class="footer-menu-icon" />{{ $item['label'] }}</a>
                    @endforeach
                    @if ($alternateLocale && $alternateUrl)
                        <a class="footer-menu-link" href="{{ $alternateUrl }}" hreflang="{{ $alternateLocale }}" lang="{{ $alternateLocale }}"><x-ui.icon name="language" class="footer-menu-icon" />{{ $alternateLocale === 'am' ? 'አማርኛ' : 'English' }}</a>
                    @endif
                </div>
            </nav>
            <div>
                <h2 class="footer-heading">{{ __('Practical insight, periodically') }}</h2>
                <form id="newsletter-form" method="POST" action="{{ route('newsletter.subscribe', ['locale' => $currentLocale]) }}" class="mt-4 grid gap-3" data-prevent-duplicate>
                    @csrf
                    <input type="hidden" name="policy_version" value="{{ $publicExperience['privacy']['policy_version'] }}">
                    <label class="text-sm font-bold text-white" for="newsletter-email">{{ __('Email address') }}</label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input id="newsletter-email" name="email" type="email" required autocomplete="email" class="form-input min-w-0 flex-1 border-slate-600 bg-brand-800 text-sm text-white placeholder:text-slate-400 focus:border-action-300 focus:ring-action-300">
                        <button class="min-h-11 rounded-lg bg-gold-500 px-5 text-sm font-bold text-brand-950 hover:bg-gold-400">{{ __('Join') }}</button>
                    </div>
                    <label class="flex items-start gap-3 text-xs leading-5 text-slate-300" for="newsletter-consent">
                        <input id="newsletter-consent" name="marketing_consent" type="checkbox" value="1" required class="mt-0.5 rounded border-slate-500 bg-brand-800 text-gold-500">
                        <span>{{ __('I agree to receive the newsletter. Double opt-in applies and I can unsubscribe at any time.') }}</span>
                    </label>
                </form>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="content-container flex flex-col justify-between gap-4 py-6 text-xs text-slate-400 lg:flex-row lg:items-center">
                <p>© {{ $identity['copyright_start_year'] }}-{{ now()->year }} {{ $copyrightOwner }}</p>
                <nav aria-label="{{ __('Legal and privacy') }}" class="flex flex-wrap items-center gap-x-5 gap-y-1">
                    @foreach ($managedNavigation['footer_legal'] as $item)
                        <a class="footer-legal-link" href="{{ $item['url'] }}"><x-ui.icon :name="$item['icon']" class="footer-legal-icon" />{{ $item['label'] }}</a>
                    @endforeach
                    @if ($publicExperience['privacy']['cookie_notice_enabled'])
                        <button type="button" class="footer-legal-link text-start" data-open-consent-preferences><x-ui.icon name="privacy" class="footer-legal-icon" />{{ __('Privacy choices') }}</button>
                    @endif
                </nav>
            </div>
        </div>
    </footer>

    @if ($publicExperience['privacy']['cookie_notice_enabled'])
        <x-ui.consent-banner :policy-version="$publicExperience['privacy']['policy_version']" />
    @endif
</body>
</html>
