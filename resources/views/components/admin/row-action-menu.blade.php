@props(['label' => null])

<x-admin.row-actions :label="$label" {{ $attributes }}>
    {{ $slot }}
</x-admin.row-actions>
