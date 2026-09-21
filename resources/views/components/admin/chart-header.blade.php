@props(['title', 'description' => null])

<div {{ $attributes->class(['flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="min-w-0">
        <h2 class="admin-card-title">{{ $title }}</h2>
        @if ($description)
            <p class="admin-card-meta mt-1">{{ $description }}</p>
        @endif
    </div>
    @isset($action)
        <div class="admin-button-group">{{ $action }}</div>
    @endisset
</div>
