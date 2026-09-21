<section
    class="home-signature-hero-section home-hero-slider"
    aria-label="{{ __('Homepage hero') }}"
    x-data="homepageHeroSlider"
    data-slide-count="{{ count($slider['slides']) }}"
    data-interval="{{ $slider['interval_ms'] }}"
    data-autoplay="{{ $slider['autoplay'] ? 'true' : 'false' }}"
    data-pause-on-hover="{{ $slider['pause_on_hover'] ? 'true' : 'false' }}"
    x-on:mouseenter="pauseFromPointer"
    x-on:mouseleave="resumeFromPointer"
    x-on:focusin="pauseFromFocus"
    x-on:focusout="resumeFromFocus"
>
    <div
        class="home-hero-slider-stage content-container py-12 sm:py-16 lg:py-20"
        x-ref="stage"
    >
        @foreach ($slider['slides'] as $slide)
            @php($position = $loop->iteration)
            <article
                class="home-hero-slide"
                data-home-hero-slide
                role="group"
                aria-roledescription="{{ __('slide') }}"
                aria-label="{{ __('Slide :current of :total', ['current' => $position, 'total' => count($slider['slides'])]) }}"
                x-show="slide{{ $position }}Visible"
                @if (! $loop->first) x-cloak @endif
                x-transition:enter="home-hero-transition-enter"
                x-transition:enter-start="home-hero-transition-enter-start"
                x-transition:enter-end="home-hero-transition-enter-end"
                x-transition:leave="home-hero-transition-leave"
                x-transition:leave-start="home-hero-transition-leave-start"
                x-transition:leave-end="home-hero-transition-leave-end"
            >
                <div class="home-signature-hero">
                    <div class="home-signature-copy">
                        <x-ui.insight-marker :label="$slide['eyebrow']" />

                        @if ($loop->first)
                            <h1 class="home-signature-title">{{ $slide['heading'] }}</h1>
                        @else
                            <h2 class="home-signature-title">{{ $slide['heading'] }}</h2>
                        @endif

                        <p class="home-signature-summary">{{ $slide['summary'] }}</p>

                        @if ($slider['primary_action'] || $slider['secondary_action'])
                            <div class="home-signature-actions">
                                @if ($slider['primary_action'])
                                    <a class="button-primary" href="{{ $slider['primary_action']['href'] }}">
                                        {{ $slider['primary_action']['label'] }} <span aria-hidden="true">&rarr;</span>
                                    </a>
                                @endif
                                @if ($slider['secondary_action'])
                                    <a class="home-signature-text-link" href="{{ $slider['secondary_action']['href'] }}">
                                        {{ $slider['secondary_action']['label'] }}
                                        <x-ui.icon name="arrow-up-right" class="size-4" />
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="home-signature-art">
                        <div class="home-signature-image">
                            <img
                                src="{{ $slide['image'] }}"
                                alt=""
                                aria-hidden="true"
                                width="1536"
                                height="1024"
                                @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                                decoding="async"
                            >
                        </div>

                        <div class="home-signature-field-note">
                            <span aria-hidden="true"><x-ui.icon name="insights" /></span>
                            <div>
                                <p>{{ __('Our method') }}</p>
                                <strong>{{ __('Read the system. Find the leverage. Build the path to delivery.') }}</strong>
                            </div>
                        </div>

                        <div class="home-signature-coordinates" aria-hidden="true">
                            <span>{{ str_pad((string) $position, 2, '0', STR_PAD_LEFT) }}</span>
                            <span>{{ str_pad((string) count($slider['slides']), 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    @if (count($slider['slides']) > 1)
        <div class="content-container">
            <div class="home-hero-slider-controls">
                <div class="home-hero-slider-dots" aria-label="{{ __('Choose a slide') }}">
                    @foreach ($slider['slides'] as $slide)
                        @php($position = $loop->iteration)
                        <button
                            type="button"
                            data-slide-target="{{ $position }}"
                            x-on:click="goToSlide"
                            x-bind:class="slide{{ $position }}DotClass"
                            x-bind:aria-current="slide{{ $position }}Current"
                            aria-label="{{ __('Slide :current of :total', ['current' => $position, 'total' => count($slider['slides'])]) }}"
                        >
                            <span>{{ str_pad((string) $position, 2, '0', STR_PAD_LEFT) }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="home-hero-slider-buttons">
                    <button type="button" x-on:click="previousSlide" aria-label="{{ __('Previous slide') }}">
                        <span aria-hidden="true">&larr;</span>
                    </button>
                    <button type="button" x-show="pauseButtonVisible" x-on:click="pauseManually" aria-label="{{ __('Pause slides') }}">
                        <span aria-hidden="true">Ⅱ</span>
                    </button>
                    <button type="button" x-show="playButtonVisible" x-cloak x-on:click="playManually" aria-label="{{ __('Play slides') }}">
                        <span class="home-hero-slider-play-icon" aria-hidden="true"></span>
                    </button>
                    <button type="button" x-on:click="nextSlide" aria-label="{{ __('Next slide') }}">
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</section>
