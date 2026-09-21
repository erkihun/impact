@props([
    'href',
    'title',
    'description' => null,
    'icon' => 'external',
])

<a href="{{ $href }}" {{ $attributes->class(['admin-quick-action-card']) }}>
    <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-action-50 text-action-700" aria-hidden="true">
        <x-admin.icon :name="$icon" class="size-5" />
    </span>
    <span class="min-w-0 flex-1">
        <span class="block font-bold text-brand-950">{{ $title }}</span>
        @if ($description)
            <span class="mt-1 block text-sm leading-6 text-muted">{{ $description }}</span>
        @endif
    </span>
    <span class="text-action-700" aria-hidden="true">-></span>
</a>
