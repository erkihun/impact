@php($usage = collect($media)->first())
@if ($usage?->asset)
    <figure>
        <img
            class="aspect-[3/2] w-full object-cover"
            src="{{ $usage->asset->publicUrl() }}"
            alt="{{ $usage->decorative ? '' : ($usage->asset->alt_text ?? '') }}"
            @if ($usage->decorative) aria-hidden="true" @endif
            loading="lazy"
            decoding="async"
        >
        @if ($usage->caption)<figcaption class="mt-3 text-sm text-muted">{{ $usage->caption }}</figcaption>@endif
    </figure>
@endif
