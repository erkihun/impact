@props(['active' => false])

<button type="button" {{ $attributes->class([$active ? 'button-primary' : 'button-secondary']) }}>
    {{ $slot }}
</button>
