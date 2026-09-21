@props(['title'])

<section {{ $attributes->class(['admin-drawer']) }} role="dialog" aria-modal="true" aria-label="{{ $title }}">
    <div class="border-b border-edge p-4">
        <h2 class="admin-card-title">{{ $title }}</h2>
    </div>
    <div class="p-4">{{ $slot }}</div>
</section>
