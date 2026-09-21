<figure class="mx-auto max-w-4xl border-s-4 border-gold-400 ps-6">
    <blockquote class="font-editorial text-xl leading-relaxed">“{{ $section->content['quote'] }}”</blockquote>
    @if ($section->content['attribution'] ?? null)<figcaption class="mt-5 font-bold">{{ $section->content['attribution'] }}@if ($section->content['role'] ?? null)<span class="font-normal text-muted"> — {{ $section->content['role'] }}</span>@endif</figcaption>@endif
</figure>
