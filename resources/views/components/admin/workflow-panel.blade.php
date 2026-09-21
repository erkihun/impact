@props(['title' => null])

<section {{ $attributes->class(['admin-attention-rail']) }}>
    @if ($title)
        <h2 class="admin-card-title">{{ $title }}</h2>
    @endif
    <div @class(['mt-4' => $title])>{{ $slot }}</div>
</section>
