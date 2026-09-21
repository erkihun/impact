@props([
    'tone' => 'teal',
    'vertical' => false,
])

<span
    {{ $attributes->class([
        'impact-line',
        'impact-line-vertical' => $vertical,
        'impact-line-gold' => $tone === 'gold',
        'impact-line-blue' => $tone === 'blue',
    ]) }}
    aria-hidden="true"
></span>
