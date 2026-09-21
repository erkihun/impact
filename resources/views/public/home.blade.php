@extends('layouts.public')

@section('title', __('Impact Consulting — Ideas into measurable change'))

@php
    $publishedEvidence = collect($impactIndex)->firstWhere('value', '>', 0);
    $featuredCaseStudy = $caseStudies->first();
    $featuredExpert = $experts->first();
@endphp

@section('content')
    @if ($heroSlider['slides'] !== [])
        @include('public.sections.homepage-slider', ['slider' => $heroSlider])
    @elseif ($managedComposition)
        <x-ui.page-composition :composition="$managedComposition" />
    @else
        <section class="home-signature-hero-section" aria-labelledby="home-hero-title">
            <div class="content-container py-12 sm:py-16 lg:py-20">
                <div class="home-signature-hero">
                    <div class="home-signature-copy">
                        <x-ui.insight-marker :label="__('Impact Intelligence')" />
                        <h1 id="home-hero-title" class="home-signature-title">
                            {{ __('Evidence for the decisions that shape institutions.') }}
                        </h1>
                        <p class="home-signature-summary">
                            {{ __('We help leaders turn complex questions into focused strategy, stronger delivery and capability that lasts.') }}
                        </p>
                        <div class="home-signature-actions">
                            <a class="button-primary" href="{{ route('consultation.create', ['locale' => app()->getLocale()]) }}">
                                {{ __('Frame a challenge') }} <span aria-hidden="true">&rarr;</span>
                            </a>
                            <a class="home-signature-text-link" href="{{ route('case-studies.index', ['locale' => app()->getLocale()]) }}">
                                {{ __('Examine our work') }} <x-ui.icon name="arrow-up-right" class="size-4" />
                            </a>
                        </div>
                        @if ($publishedEvidence)
                            <div class="home-signature-evidence">
                                <strong>{{ $publishedEvidence['value'] }}</strong>
                                <span>
                                    <b>{{ $publishedEvidence['label'] }}</b>
                                    {{ $publishedEvidence['context'] }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="home-signature-art">
                        <div class="home-signature-image">
                            <img
                                src="{{ asset('images/impact-intelligence-hero-v1.webp') }}"
                                width="1536"
                                height="1024"
                                alt=""
                                aria-hidden="true"
                                fetchpriority="high"
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
                            <span>08.9806</span>
                            <span>38.7578</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if (collect($impactIndex)->contains(fn (array $item): bool => $item['value'] > 0))
        <section class="home-proof-section" aria-labelledby="home-proof-title">
            <div class="content-container">
                <div class="home-proof-heading">
                    <p id="home-proof-title">{{ __('Current publication record') }}</p>
                    <span aria-hidden="true"></span>
                    <small>{{ __('Published content index') }}</small>
                </div>
                <div class="home-proof-ledger">
                    @foreach (collect($impactIndex)->filter(fn (array $item): bool => $item['value'] > 0)->values() as $item)
                        @if (filled($item['href'] ?? null))
                            <a class="home-proof-item group" href="{{ $item['href'] }}">
                                <span class="home-proof-number">{{ $item['value'] }}</span>
                                <span class="home-proof-copy">
                                    <strong>{{ $item['label'] }}</strong>
                                    <small>{{ $item['context'] }}</small>
                                </span>
                                <span class="home-proof-arrow" aria-hidden="true">&nearr;</span>
                            </a>
                        @else
                            <article class="home-proof-item">
                                <span class="home-proof-number">{{ $item['value'] }}</span>
                                <span class="home-proof-copy">
                                    <strong>{{ $item['label'] }}</strong>
                                    <small>{{ $item['context'] }}</small>
                                </span>
                            </article>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($services->isNotEmpty())
        <section class="home-capability-section" aria-labelledby="home-services-title">
            <div class="content-container">
                <div class="home-section-lead">
                    <div>
                        <x-ui.insight-marker :label="__('Advisory architecture')" />
                        <h2 id="home-services-title">{{ __('Where complex problems become workable choices') }}</h2>
                    </div>
                    <div>
                        <p>{{ __('Each capability begins with the decision or delivery problem—not a pre-packaged solution.') }}</p>
                        <a class="text-link" href="{{ route('services.index', ['locale' => app()->getLocale()]) }}">
                            {{ __('Explore the capability system') }} <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </div>

                <div class="home-capability-register" aria-label="{{ __('Advisory architecture') }}">
                    @foreach ($services as $service)
                        <a class="home-capability-row group" href="{{ route('services.show', ['locale' => app()->getLocale(), 'slug' => $service->slug]) }}">
                            <span class="home-capability-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3>{{ $service->name }}</h3>
                            <p>{{ $service->summary ?: $service->problem_statement }}</p>
                            <span class="home-capability-arrow" aria-hidden="true">&rarr;</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($featuredCaseStudy)
        <section class="home-record-section" aria-labelledby="home-case-title">
            <div class="content-container">
                <article class="home-record">
                    <div class="home-record-media">
                        <img
                            src="{{ asset('images/impact-intelligence-hero-v1.webp') }}"
                            width="1536"
                            height="1024"
                            alt=""
                            loading="lazy"
                            decoding="async"
                        >
                        <span aria-hidden="true">01</span>
                    </div>
                    <div class="home-record-copy">
                        <x-ui.insight-marker :label="__('Transformation record')" tone="gold" />
                        <h2 id="home-case-title">{{ $featuredCaseStudy->title }}</h2>

                        <div class="home-record-details">
                            @if ($featuredCaseStudy->challenge)
                                <div>
                                    <p>{{ __('The question') }}</p>
                                    <span>{{ str($featuredCaseStudy->challenge)->limit(220) }}</span>
                                </div>
                            @endif
                            @if ($featuredCaseStudy->approach)
                                <div>
                                    <p>{{ __('The response') }}</p>
                                    <span>{{ str($featuredCaseStudy->approach)->limit(220) }}</span>
                                </div>
                            @endif
                        </div>

                        @if ($featuredCaseStudy->outcomes)
                            <blockquote>{{ str($featuredCaseStudy->outcomes)->limit(320) }}</blockquote>
                        @endif

                        <a href="{{ route('case-studies.show', ['locale' => app()->getLocale(), 'slug' => $featuredCaseStudy->slug]) }}">
                            {{ __('Examine the full record') }} <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </article>
            </div>
        </section>
    @endif

    @if ($industries->isNotEmpty())
        <section class="home-sector-section" aria-labelledby="home-industries-title">
            <div class="content-container">
                <div class="home-sector-heading">
                    <div>
                        <x-ui.insight-marker :label="__('Sector intelligence')" tone="blue" />
                        <h2 id="home-industries-title">{{ __('Context changes the answer') }}</h2>
                    </div>
                    <p>{{ __('Our sector work starts with institutions, incentives and operating realities. Explore the published contexts in which we advise.') }}</p>
                </div>

                <div class="home-sector-matrix">
                    @foreach ($industries as $industry)
                        <a class="home-sector-cell group" href="{{ route('industries.show', ['locale' => app()->getLocale(), 'slug' => $industry->slug]) }}">
                            <div>
                                <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span aria-hidden="true"><x-ui.icon name="industries" /></span>
                            </div>
                            <h3>{{ $industry->name }}</h3>
                            @if ($industry->summary)
                                <p>{{ $industry->summary }}</p>
                            @endif
                            <span class="home-sector-link" aria-hidden="true">&rarr;</span>
                        </a>
                    @endforeach
                </div>

                <a class="text-link mt-8" href="{{ route('industries.index', ['locale' => app()->getLocale()]) }}">
                    {{ __('Explore all sector contexts') }} <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </section>
    @endif

    @if ($featuredExpert)
        <section class="home-perspectives-section" aria-label="{{ __('Expert perspective') }}">
            <div class="content-container">
                <article class="home-person-panel">
                    <x-ui.insight-marker :label="__('Expert perspective')" />
                    <div class="home-expert-card-grid">
                        @foreach ($experts->take(3) as $expert)
                            @php($expertPhoto = $expert->expert?->profileMedia)
                            <a class="home-expert-card" href="{{ route('experts.show', ['locale' => app()->getLocale(), 'slug' => $expert->slug]) }}">
                                <span class="home-expert-card-photo">
                                    @if ($expertPhoto?->isPubliclyUsable())
                                        <img src="{{ $expertPhoto->publicUrl() }}" alt="{{ $expertPhoto->alt_text ?: $expert->display_name }}" loading="lazy" decoding="async">
                                    @else
                                        <span aria-hidden="true">{{ str($expert->display_name)->substr(0, 1)->upper() }}</span>
                                    @endif
                                </span>
                                <span class="home-expert-card-body">
                                    <strong>{{ $expert->display_name }}</strong>
                                    <small>{{ $expert->professional_title }}</small>
                                    @if ($expert->biography)
                                        <span>{{ str($expert->biography)->limit(135) }}</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </article>

                <div class="home-perspectives-links home-perspectives-links-single">
                    <a class="text-link" href="{{ route('experts.index', ['locale' => app()->getLocale()]) }}">
                        {{ __('Meet the advisory team') }} <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        </section>
    @endif

    <section class="home-next-move-section" aria-labelledby="home-engagement-title">
        <div class="content-container">
            <div class="home-next-move">
                <div>
                    <x-ui.insight-marker :label="__('Engagement pathway')" tone="gold" />
                    <h2 id="home-engagement-title">{{ __('Bring the question. We will help structure the next move.') }}</h2>
                    <p>{{ __('Share the outcome, context, stakeholders and timing. Our team will review the request and identify the most relevant response.') }}</p>
                </div>
                <div>
                    <ol>
                        @foreach ([__('Submit the essential context'), __('Receive a structured review'), __('Agree the right engagement route')] as $step)
                            <li>
                                <span>0{{ $loop->iteration }}</span>
                                <strong>{{ $step }}</strong>
                            </li>
                        @endforeach
                    </ol>
                    <div class="home-next-move-actions">
                        <a class="button-light" href="{{ route('consultation.create', ['locale' => app()->getLocale()]) }}">
                            {{ __('Request a consultation') }} <span aria-hidden="true">&rarr;</span>
                        </a>
                        <a href="{{ route('contact.create', ['locale' => app()->getLocale()]) }}">
                            {{ __('Choose another route') }} <x-ui.icon name="arrow-up-right" class="size-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
