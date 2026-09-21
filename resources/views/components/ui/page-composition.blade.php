@props(['composition', 'headingOverride' => null, 'summaryOverride' => null])

@php
    $surfaceClasses = [
        'white' => 'bg-white text-brand-950',
        'paper' => 'bg-paper text-brand-950',
        'muted' => 'bg-slate-100 text-brand-950',
        'brand' => 'bg-brand-950 text-white',
        'gold' => 'bg-gold-100 text-brand-950',
    ];
    $widthClasses = [
        'reading' => 'mx-auto max-w-3xl px-5 sm:px-8',
        'standard' => 'content-container',
        'wide' => 'content-container max-w-screen-2xl',
        'full' => 'w-full',
    ];
    $spacingClasses = ['none' => 'py-0', 'compact' => 'py-8 sm:py-10', 'standard' => 'public-section', 'spacious' => 'py-20 sm:py-28'];
@endphp

@foreach ($composition->sections as $section)
    @php
        $isHomepageHero = $section->type->value === 'homepage_hero';
        $surface = $surfaceClasses[$section->presentation['surface_tone'] ?? 'white'] ?? $surfaceClasses['white'];
        $width = $widthClasses[$section->presentation['container_width'] ?? 'standard'] ?? $widthClasses['standard'];
        $spacing = $spacingClasses[$section->presentation['spacing_top'] ?? 'standard'] ?? $spacingClasses['standard'];
    @endphp
    <section
        id="{{ $section->stableKey }}"
        class="{{ $isHomepageHero ? 'home-signature-hero-section' : $surface }}"
        data-section-type="{{ $section->type->value }}"
        data-section-version="{{ $section->id }}"
    >
        <div class="{{ $isHomepageHero ? 'content-container' : $width }} {{ $isHomepageHero ? 'py-12 sm:py-16 lg:py-20' : $spacing }}">
            @include($section->renderer, [
                'section' => $section,
                'headingOverride' => $headingOverride,
                'summaryOverride' => $summaryOverride,
            ])
        </div>
    </section>
@endforeach
