@props([
    'label' => null,
])

{{--
    Row action group. Actions use the shared button system rather than bare text links,
    so they keep a 44px target and a visible focus ring on every screen size.
--}}
<td class="admin-cell lg:text-end" @if ($label) data-label="{{ $label }}" @endif>
    <div class="admin-row-actions">{{ $slot }}</div>
</td>
