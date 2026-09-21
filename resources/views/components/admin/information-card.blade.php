@props(['title', 'description' => null])

<x-admin.card :title="$title" :description="$description" {{ $attributes->class(['border-s-4 border-s-knowledge-600']) }}>
    {{ $slot }}
</x-admin.card>
