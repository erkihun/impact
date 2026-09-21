@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

{{--
    The single admin page header. Every admin screen uses this so the eyebrow, title,
    description and primary action sit in the same place at the same size.
    Pass the primary action through the `action` slot.
--}}
<div class="grid gap-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-2 font-editorial text-2xl font-bold text-brand-950 sm:text-3xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($action)
        <div class="flex flex-wrap gap-2 sm:justify-end">{{ $action }}</div>
    @endisset
</div>
