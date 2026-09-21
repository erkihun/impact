@props([
    'tone' => 'info',
    'title' => null,
])

@php
    $class = match ($tone) {
        'success' => 'admin-alert-success',
        'warning' => 'admin-alert-warning',
        'danger', 'error' => 'admin-alert-danger',
        default => 'admin-alert-info',
    };
@endphp

<div {{ $attributes->class(['admin-alert', $class]) }} role="{{ in_array($tone, ['danger', 'error'], true) ? 'alert' : 'status' }}">
    @if ($title)
        <p class="font-bold text-brand-950">{{ $title }}</p>
    @endif
    <div @class(['mt-1' => $title])>{{ $slot }}</div>
</div>
