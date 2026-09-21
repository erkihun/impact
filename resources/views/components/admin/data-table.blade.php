@props(['columns' => [], 'label' => null])

<x-admin.record-list :columns="$columns" :label="$label" {{ $attributes }}>
    {{ $slot }}
</x-admin.record-list>
