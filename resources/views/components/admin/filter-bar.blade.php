@props([
    'action' => null,
    'method' => 'GET',
    'hasActiveFilters' => false,
    'clearUrl' => null,
    'legend' => null,
])

{{--
    Shared filter rail for admin lists. Renders as a real form so filtering works
    without JavaScript; `Clear` only appears when a filter is actually applied.
--}}
<form
    class="impact-paper grid gap-4 border-t-4 border-brand-950 p-5 md:grid-cols-[repeat(auto-fit,minmax(11rem,1fr))] md:items-end"
    method="{{ $method }}"
    @if ($action) action="{{ $action }}" @endif
    role="search"
    @if ($legend) aria-label="{{ $legend }}" @endif
>
    {{ $slot }}

    <div class="flex flex-wrap gap-2 md:justify-start">
        <button class="button-primary" type="submit">{{ __('Apply filters') }}</button>
        @if ($hasActiveFilters && $clearUrl)
            <a class="button-secondary" href="{{ $clearUrl }}">{{ __('Clear filters') }}</a>
        @endif
    </div>
</form>
