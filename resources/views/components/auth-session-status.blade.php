@props(['status'])

@if ($status)
    <div role="status" {{ $attributes->merge(['class' => 'status-success']) }}>
        {{ $status }}
    </div>
@endif
