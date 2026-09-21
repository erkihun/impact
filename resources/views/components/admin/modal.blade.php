@props(['name', 'title'])

<x-modal :name="$name" {{ $attributes }}>
    <div class="p-6">
        <h2 class="admin-card-title">{{ $title }}</h2>
        <div class="mt-4">{{ $slot }}</div>
    </div>
</x-modal>
