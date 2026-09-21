@props([
    'label',
    'icon',
    'type' => 'button',
])

<button type="{{ $type }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class(['admin-icon-button']) }}>
    <x-admin.icon :name="$icon" class="size-5" />
</button>
