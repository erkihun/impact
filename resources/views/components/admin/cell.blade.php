@props([
    'label' => null,
    'primary' => false,
    'numeric' => false,
])

{{--
    A record cell. `label` is echoed as the stacked-view field label on small screens,
    so the mobile record card stays self-describing without a second markup path.
--}}
<td
    {{ $attributes->class([
        'admin-cell',
        'admin-cell-primary' => $primary,
        'admin-cell-numeric' => $numeric,
    ]) }}
    @if ($label) data-label="{{ $label }}" @endif
>
    <span class="admin-cell-value">{{ $slot }}</span>
</td>
