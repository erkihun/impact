@props(['messages'])

@if ($messages)
    <p {{ $attributes->class(['field-error']) }}>
        {{ is_array($messages) ? implode(' ', $messages) : $messages }}
    </p>
@endif
