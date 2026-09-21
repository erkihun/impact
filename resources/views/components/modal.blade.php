@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'labelledby' => null,
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

{{--
    The CSP Alpine build resolves directives to registered properties and methods only:
    inline expressions such as `show && closeModal()` are never evaluated. Every binding
    below is therefore a bare method or property name, with the modal name passed as data.
--}}
<div
    x-data="modal"
    data-modal-name="{{ $name }}"
    data-modal-show="{{ $show ? 'true' : 'false' }}"
    x-on:open-modal.window="openFromEvent"
    x-on:close-modal.window="closeFromEvent"
    x-on:keydown.escape.window="closeOnEscape"
    x-on:keydown="trapModal"
    x-cloak
    x-show="show"
    class="fixed inset-0 z-[80] overflow-y-auto px-4 py-8"
    role="dialog"
    aria-modal="true"
    @if ($labelledby) aria-labelledby="{{ $labelledby }}" @else aria-label="{{ __('Confirmation') }}" @endif
>
    <div class="fixed inset-0 bg-brand-950/70" x-on:click="closeModal" aria-hidden="true"></div>
    <div
        x-ref="dialog"
        x-show="show"
        x-transition.opacity
        class="relative mx-auto overflow-hidden rounded-xl bg-white shadow-overlay {{ $maxWidth }}"
    >
        {{ $slot }}
    </div>
</div>
