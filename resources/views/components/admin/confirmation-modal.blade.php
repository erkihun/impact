@props(['name', 'title', 'description' => null])

<x-admin.modal :name="$name" :title="$title" {{ $attributes }}>
    @if ($description)
        <p class="admin-card-meta">{{ $description }}</p>
    @endif
    <div class="mt-6">{{ $slot }}</div>
</x-admin.modal>
