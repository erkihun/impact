@props(['title', 'description' => null, 'id' => null])

<x-admin.section :title="$title" :description="$description" :id="$id" {{ $attributes }}>
    {{ $slot }}
</x-admin.section>
