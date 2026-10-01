@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'variant' => 'standard',
    'meta' => null,
])

<header {{ $attributes->class(['public-page-header m-page-header']) }}>
    <div class="content-container">
        @if ($eyebrow)
            <x-ui.insight-marker :label="$eyebrow" />
        @endif
        <h1>{{ $title }}</h1>
        @if ($description)
            <p class="m-lead">{{ $description }}</p>
        @endif
        @if ($meta)
            <p class="m-meta">{{ $meta }}</p>
        @endif
        @if (trim((string) $slot) !== '')
            <div class="m-actions">{{ $slot }}</div>
        @endif
    </div>
</header>
