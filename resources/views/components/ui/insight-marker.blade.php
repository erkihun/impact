@props([
    'label' => null,
    'tone' => 'blue',
])

<span {{ $attributes->class(['insight-marker', 'insight-marker-gold' => $tone === 'gold']) }}>
    <span class="insight-marker-symbol" aria-hidden="true"></span>
    @if ($label)<span>{{ $label }}</span>@endif
</span>
