@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <span class="mx-auto grid size-12 place-items-center rounded-full bg-action-50 text-action-700" aria-hidden="true">—</span>
    <h2 class="heading-3 mt-5">{{ $title }}</h2>
    @if ($description)
        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-muted">{{ $description }}</p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
