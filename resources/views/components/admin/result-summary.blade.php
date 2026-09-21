@props([
    'paginator' => null,
    'total' => null,
    'context' => null,
])

@php
    $count = $total ?? ($paginator?->total() ?? 0);
@endphp

{{-- Result count for a list, required so filtering always reports what it produced. --}}
<div class="editorial-rule-heading mt-8">
    <p class="text-sm font-bold text-brand-950" role="status">
        {{ trans_choice(':count record|:count records', $count, ['count' => number_format($count)]) }}
    </p>
    @if ($context)
        <p class="font-mono text-xs font-bold uppercase tracking-[0.14em] text-muted">{{ $context }}</p>
    @endif
</div>
