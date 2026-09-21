@props(['title' => null])

<x-admin.drawer :title="$title ?? __('Filters')" {{ $attributes }}>
    {{ $slot }}
</x-admin.drawer>
