@props([
    'title',
    'status',
    'updated' => null,
])

<x-admin.card :title="$title" {{ $attributes }}>
    <div class="flex flex-wrap items-center gap-3">
        <x-ui.status-badge :status="$status" />
        @if ($updated)
            <span class="text-sm text-muted">{{ $updated }}</span>
        @endif
    </div>
    @if (trim((string) $slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</x-admin.card>
