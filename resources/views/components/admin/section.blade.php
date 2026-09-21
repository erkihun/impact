@props([
    'title',
    'description' => null,
    'id' => null,
])

{{-- Groups related fields or content. Used to break long forms into readable sections. --}}
<section @if ($id) id="{{ $id }}" @endif {{ $attributes->class(['admin-section']) }}>
    <div class="grid gap-2">
        <h2 class="font-editorial text-lg font-bold text-brand-950">{{ $title }}</h2>
        @if ($description)
            <p class="max-w-2xl text-sm leading-6 text-muted">{{ $description }}</p>
        @endif
    </div>
    <div class="mt-5">{{ $slot }}</div>
</section>
