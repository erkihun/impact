@php($heroMedia = collect($section->media)->first())

<div class="home-signature-hero">
    <div class="home-signature-copy">
        <x-ui.insight-marker :label="$section->content['eyebrow'] ?? __('Impact Intelligence')" />

        <h1 class="home-signature-title">{{ $section->content['heading'] }}</h1>

        @if ($section->content['summary'] ?? null)
            <p class="home-signature-summary">{{ $section->content['summary'] }}</p>
        @endif

        @if (count($section->actions) > 0)
            @include('public.sections.partials.actions', ['actions' => $section->actions])
        @else
            <div class="home-signature-actions">
                <a class="button-primary" href="{{ route('consultation.create', ['locale' => app()->getLocale()]) }}">
                    {{ __('Request a consultation') }} <span aria-hidden="true">&rarr;</span>
                </a>
                <a class="home-signature-text-link" href="{{ route('case-studies.index', ['locale' => app()->getLocale()]) }}">
                    {{ __('Examine our work') }} <x-ui.icon name="arrow-up-right" class="size-4" />
                </a>
            </div>
        @endif

        @if (($section->content['evidence_value'] ?? null) && ($section->content['evidence_label'] ?? null))
            <div class="home-signature-evidence">
                <strong>{{ $section->content['evidence_value'] }}</strong>
                <span>{{ $section->content['evidence_label'] }}</span>
            </div>
        @endif
    </div>

    <div class="home-signature-art">
        <div class="home-signature-image">
            @if ($heroMedia?->asset)
                <img
                    src="{{ $heroMedia->asset->publicUrl() }}"
                    alt="{{ $heroMedia->decorative ? '' : ($heroMedia->asset->alt_text ?? '') }}"
                    @if ($heroMedia->decorative) aria-hidden="true" @endif
                    fetchpriority="high"
                    decoding="async"
                >
            @else
                <img
                    src="{{ asset('images/impact-intelligence-hero-v1.webp') }}"
                    width="1536"
                    height="1024"
                    alt=""
                    aria-hidden="true"
                    fetchpriority="high"
                    decoding="async"
                >
            @endif
        </div>

        <div class="home-signature-field-note">
            <span aria-hidden="true"><x-ui.icon name="insights" /></span>
            <div>
                <p>{{ __('Our method') }}</p>
                <strong>{{ __('Read the system. Find the leverage. Build the path to delivery.') }}</strong>
            </div>
        </div>

        <div class="home-signature-coordinates" aria-hidden="true">
            <span>08.9806</span>
            <span>38.7578</span>
        </div>
    </div>
</div>
