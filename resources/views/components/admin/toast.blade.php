@props(['tone' => 'info'])

<x-admin.alert :tone="$tone" {{ $attributes }}>
    {{ $slot }}
</x-admin.alert>
