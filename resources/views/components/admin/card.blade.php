@props([
    'title' => null,
    'description' => null,
    'interactive' => false,
])

<section {{ $attributes->class(['admin-card', 'admin-card-interactive' => $interactive]) }}>
    @if ($title || $description || isset($header))
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="admin-card-title">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="admin-card-meta mt-1">{{ $description }}</p>
                @endif
            </div>
            @isset($header)
                <div>{{ $header }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</section>
