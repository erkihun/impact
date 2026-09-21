@props([
    'label',
    'value',
    'context' => null,
])

<article {{ $attributes->class(['admin-kpi']) }}>
    <p class="admin-kpi-label">{{ $label }}</p>
    <p class="admin-kpi-value">{{ $value }}</p>
    @if ($context)
        <p class="mt-2 text-sm leading-6 text-muted">{{ $context }}</p>
    @endif
</article>
