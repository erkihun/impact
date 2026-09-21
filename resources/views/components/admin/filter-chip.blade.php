@props(['href' => null])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['admin-filter-chip']) }}>{{ $slot }}</a>
@else
    <span {{ $attributes->class(['admin-filter-chip']) }}>{{ $slot }}</span>
@endif
