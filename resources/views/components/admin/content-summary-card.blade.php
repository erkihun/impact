@props([
    'title',
    'type',
    'locale',
    'owner',
    'status',
    'updated' => null,
    'href' => null,
])

<article {{ $attributes->class(['admin-card', 'admin-card-interactive' => $href]) }}>
    <div class="flex flex-wrap items-center gap-2">
        <x-ui.status-badge :status="$status" />
        <span class="text-xs font-bold uppercase tracking-[0.12em] text-muted">{{ $type }} · {{ $locale }}</span>
    </div>
    <h2 class="mt-3 admin-card-title">
        @if ($href)
            <a class="hover:text-action-700" href="{{ $href }}">{{ $title }}</a>
        @else
            {{ $title }}
        @endif
    </h2>
    <p class="mt-2 admin-card-meta">{{ __('Owner') }}: {{ $owner }}</p>
    @if ($updated)
        <p class="mt-1 text-xs text-muted">{{ $updated }}</p>
    @endif
</article>
