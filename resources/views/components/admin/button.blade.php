@props([
    'variant' => 'primary',
    'type' => 'button',
    'loading' => false,
])

@php
    $class = match ($variant) {
        'secondary' => 'button-secondary',
        'tertiary' => 'button-tertiary',
        'danger', 'destructive' => 'button-danger',
        default => 'button-primary',
    };
@endphp

<button
    type="{{ $type }}"
    @disabled($attributes->get('disabled') || $loading)
    @if ($loading) aria-busy="true" @endif
    {{ $attributes->class([$class]) }}
>
    @if ($loading)
        <span class="admin-skeleton size-4" aria-hidden="true"></span>
    @endif
    <span>{{ $slot }}</span>
</button>
