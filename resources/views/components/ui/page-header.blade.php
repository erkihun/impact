@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'variant' => 'standard',
    'meta' => null,
])

@php
    $knowledge = $variant === 'knowledge';
    $paper = $variant === 'paper';
@endphp

<header {{ $attributes->class([
    'public-page-header relative overflow-hidden',
    'page-hero' => ! $paper,
    'impact-paper border-y border-slate-300 text-ink' => $paper,
    'bg-knowledge-950' => $knowledge,
]) }}>
    <div class="page-header-layout content-container">
        <div>
            @if ($eyebrow)
                <x-ui.insight-marker
                    :label="$eyebrow"
                    :tone="$paper ? 'gold' : ($knowledge ? 'blue' : 'gold')"
                    @class(['text-action-100' => ! $paper && ! $knowledge, 'text-knowledge-100' => $knowledge])
                />
            @endif
            <h1 @class([
                'heading-1 mt-6 max-w-5xl',
                'text-white' => ! $paper,
                'text-brand-950' => $paper,
            ])>{{ $title }}</h1>
            @if ($description)
                <p @class([
                    'reading-width mt-7 text-base leading-8 sm:text-lg',
                    'text-slate-300' => ! $paper,
                    'text-muted' => $paper,
                ])>{{ $description }}</p>
            @endif
            @if ($meta)
                <p class="mt-6 text-sm font-semibold {{ $paper ? 'text-knowledge-700' : 'text-action-100' }}">{{ $meta }}</p>
            @endif
            @if (trim((string) $slot) !== '')
                <div class="mt-9 flex flex-wrap gap-3">{{ $slot }}</div>
            @endif
        </div>
        <div class="page-header-aside {{ $paper ? 'text-brand-950' : 'text-white' }}">
            <p class="page-header-kicker {{ $paper ? 'text-muted' : 'text-slate-400' }}">
                {{ __('Impact Intelligence') }}
            </p>
            <x-ui.impact-line :tone="$knowledge ? 'blue' : 'gold'" />
            <div class="page-header-index" aria-hidden="true">
                <span>I</span>
            </div>
        </div>
    </div>
</header>
