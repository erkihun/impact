@props([
    'title',
    'description' => null,
    'summary' => null,
])

<section {{ $attributes->class(['admin-chart-card']) }}>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h2 class="admin-card-title">{{ $title }}</h2>
            @if ($description)
                <p class="admin-card-meta mt-1">{{ $description }}</p>
            @endif
        </div>
        @isset($toolbar)
            <div class="admin-chart-toolbar">{{ $toolbar }}</div>
        @endisset
    </div>
    <div class="mt-5">{{ $slot }}</div>
    @if ($summary)
        <p class="admin-chart-summary mt-5">{{ $summary }}</p>
    @endif
</section>
