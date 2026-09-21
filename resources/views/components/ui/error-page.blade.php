@props([
    'code',
    'title',
    'description',
    'actionLabel' => null,
    'actionUrl' => null,
    'showRecoveryRoutes' => true,
])

@php
    $locale = app()->getLocale();
    $recoveryRoutes = [
        ['label' => __('Search the site'), 'description' => __('Find a page, report or expert by keyword.'), 'url' => route('search', ['locale' => $locale])],
        ['label' => __('Browse services'), 'description' => __('See how we help organizations solve complex problems.'), 'url' => route('services.index', ['locale' => $locale])],
        ['label' => __('Read insights'), 'description' => __('Articles, reports and analysis from our published work.'), 'url' => route('insights.index', ['locale' => $locale])],
        ['label' => __('Contact us'), 'description' => __('Reach the right team and we will respond.'), 'url' => route('contact.create', ['locale' => $locale])],
    ];
@endphp

<section class="content-container py-20 sm:py-28">
    <div class="mx-auto max-w-3xl text-center">
        <p class="eyebrow">{{ $code }}</p>
        <h1 class="heading-1 mt-4 text-brand-950">{{ $title }}</h1>
        <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-muted sm:text-lg">{{ $description }}</p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <a class="button-primary" href="{{ $actionUrl ?? route('localized-home', ['locale' => $locale]) }}">
                {{ $actionLabel ?? __('Return to the homepage') }}
            </a>
            <a class="button-secondary" href="{{ route('contact.create', ['locale' => $locale]) }}">{{ __('Contact us') }}</a>
        </div>
    </div>

    {{-- Recovery routes required by the specification: search, services, insights and contact. --}}
    @if ($showRecoveryRoutes)
        <nav class="mx-auto mt-16 max-w-5xl" aria-labelledby="error-recovery-heading">
            <h2 id="error-recovery-heading" class="eyebrow text-center">{{ __('Where to go next') }}</h2>
            <ul class="mt-6 grid gap-px overflow-hidden border border-slate-300 bg-slate-300 sm:grid-cols-2">
                @foreach ($recoveryRoutes as $route)
                    <li class="bg-white">
                        <a class="flex h-full flex-col p-6 transition hover:bg-quiet" href="{{ $route['url'] }}">
                            <span class="text-base font-bold text-brand-900">{{ $route['label'] }}</span>
                            <span class="mt-2 text-sm leading-6 text-muted">{{ $route['description'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
</section>
