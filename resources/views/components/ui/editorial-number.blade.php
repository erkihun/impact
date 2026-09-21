@props(['number'])

<span {{ $attributes->class(['editorial-number']) }} aria-hidden="true">
    {{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}
</span>
