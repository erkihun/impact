@props([
    'title',
    'count' => null,
    'tone' => 'warning',
])

<section {{ $attributes->class(['admin-card border-s-4', 'border-s-state-warning' => $tone === 'warning', 'border-s-state-danger' => $tone === 'danger', 'border-s-action-500' => ! in_array($tone, ['warning', 'danger'], true)]) }}>
    <div class="flex items-start justify-between gap-4">
        <h2 class="admin-card-title">{{ $title }}</h2>
        @if ($count !== null)
            <span class="font-mono text-2xl font-black text-brand-950">{{ $count }}</span>
        @endif
    </div>
    <div class="mt-4">{{ $slot }}</div>
</section>
