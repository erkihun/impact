@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white'])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    '56' => 'w-56',
    default => $width,
};
@endphp

<div class="relative" x-data="dropdown" x-on:click.outside="close" x-on:keydown.escape="close(true)">
    <div x-on:click="toggle">
        {{ $trigger }}
    </div>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        x-ref="menu"
        class="absolute z-50 mt-2 {{ $width }} rounded-lg border border-slate-200 shadow-overlay {{ $alignmentClasses }}"
        x-on:click="close"
    >
        <div class="overflow-hidden rounded-lg {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
