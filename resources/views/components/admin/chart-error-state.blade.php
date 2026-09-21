@props(['title', 'message'])

<x-admin.alert tone="error" :title="$title" {{ $attributes }}>
    {{ $message }}
</x-admin.alert>
