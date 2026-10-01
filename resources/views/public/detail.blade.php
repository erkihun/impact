@extends('layouts.public')

@php
    $pageTitle = data_get($item, $titleField);
    $type = class_basename($item);
    $typeLabel = match ($type) {
        'ServiceVersion' => __('Service'),
        'IndustryVersion' => __('Industry'),
        'ExpertVersion' => __('Expert'),
        'CaseStudyVersion' => __('Case study'),
        'InsightVersion' => __('Insight'),
        default => __(str($type)->replace('Version', '')->headline()->toString()),
    };
    $indexRoute = match ($type) {
        'ServiceVersion' => 'services.index',
        'IndustryVersion' => 'industries.index',
        'ExpertVersion' => 'experts.index',
        'CaseStudyVersion' => 'case-studies.index',
        'InsightVersion' => 'insights.index',
        default => null,
    };
    $consultationParameters = ['locale' => app()->getLocale()];
    if ($type === 'ServiceVersion') {
        $consultationParameters['service_id'] = $item->service_id;
    } elseif ($type === 'IndustryVersion') {
        $consultationParameters['industry_id'] = $item->industry_id;
    }
    $expertPhoto = $type === 'ExpertVersion' ? $item->expert?->profileMedia : null;
    $summary = data_get($item, 'summary') ?? data_get($item, 'excerpt') ?? data_get($item, 'professional_title');
@endphp

@section('title', $pageTitle . ' — ' . __('Impact Consulting'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        __('Home') => route('localized-home', ['locale' => app()->getLocale()]),
        $typeLabel => $indexRoute ? route($indexRoute, ['locale' => app()->getLocale()]) : null,
        $pageTitle => null,
    ]" />
@endsection

@section('content')
    @if ($managedComposition)
        <x-ui.page-composition
            :composition="$managedComposition"
            :heading-override="$pageTitle"
            :summary-override="$summary"
        />
    @else
        <x-ui.page-header :eyebrow="$typeLabel" :title="$pageTitle" :description="$summary">
            <a class="button-primary" href="{{ route('consultation.create', $consultationParameters) }}">{{ __('Request advice') }}</a>
            @if ($indexRoute)
                <a class="button-secondary" href="{{ route($indexRoute, ['locale' => app()->getLocale()]) }}">{{ __('Browse all :type', ['type' => str($typeLabel)->lower()]) }}</a>
            @endif
        </x-ui.page-header>
    @endif

    <div class="content-container">
        <dl class="m-facts" aria-label="{{ __('At a glance') }}">
            <div><dt>{{ __('Content type') }}</dt><dd>{{ $typeLabel }}</dd></div>
            @if (filled(data_get($item, 'professional_title')))
                <div><dt>{{ __('Professional title') }}</dt><dd>{{ $item->professional_title }}</dd></div>
            @endif
            @if (is_array(data_get($item, 'languages')) && count($item->languages))
                <div><dt>{{ __('Languages') }}</dt><dd>{{ collect($item->languages)->join(', ') }}</dd></div>
            @endif
            @if (filled(data_get($item, 'updated_at')))
                <div><dt>{{ __('Updated') }}</dt><dd><time datetime="{{ $item->updated_at->toAtomString() }}">{{ $item->updated_at->locale(app()->getLocale())->translatedFormat('d M Y') }}</time></dd></div>
            @endif
        </dl>
    </div>

    <article class="m-section content-container">
        <div class="m-reading">
            @if ($type === 'ServiceVersion')
                @if ($item->problem_statement)
                    <section data-reveal>
                        <p class="m-eyebrow">{{ __('Client challenges') }}</p>
                        <h2>{{ __('The challenge this service addresses') }}</h2>
                        <p class="m-body">{{ $item->problem_statement }}</p>
                    </section>
                @endif
                @if ($item->approach)
                    <section data-reveal>
                        <p class="m-eyebrow">{{ __('Approach') }}</p>
                        <h2>{{ __('How we work with you') }}</h2>
                        <p class="m-body">{{ $item->approach }}</p>
                    </section>
                @endif
                @if (is_array($item->deliverables) && count($item->deliverables))
                    <section data-reveal>
                        <p class="m-eyebrow">{{ __('Deliverables') }}</p>
                        <h2>{{ __('What the engagement can produce') }}</h2>
                        <ol class="m-steps-list">
                            @foreach ($item->deliverables as $deliverable)
                                <li><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $deliverable }}</span></li>
                            @endforeach
                        </ol>
                    </section>
                @endif
                @if ($item->benefits)
                    <section data-reveal>
                        <p class="m-eyebrow">{{ __('Expected outcomes') }}</p>
                        <h2>{{ __('The value we work toward') }}</h2>
                        <p class="m-body">{{ $item->benefits }}</p>
                    </section>
                @endif
            @elseif ($type === 'IndustryVersion')
                @if ($item->overview)
                    <section data-reveal>
                        <p class="m-eyebrow">{{ __('Sector context') }}</p>
                        <h2>{{ __('Understanding the operating environment') }}</h2>
                        <p class="m-body">{{ $item->overview }}</p>
                    </section>
                @endif
                @if ($item->challenges)
                    <section data-reveal>
                        <p class="m-eyebrow">{{ __('Sector challenges') }}</p>
                        <h2>{{ __('Issues shaping decisions and delivery') }}</h2>
                        <p class="m-body">{{ $item->challenges }}</p>
                    </section>
                @endif
            @elseif ($type === 'ExpertVersion')
                <section data-reveal>
                    <div class="m-portrait">
                        @if ($expertPhoto?->isPubliclyUsable())
                            <img src="{{ $expertPhoto->publicUrl() }}" alt="{{ $expertPhoto->alt_text ?: $item->display_name }}" loading="eager" decoding="async">
                        @else
                            <span aria-hidden="true">{{ str($item->display_name)->substr(0, 1)->upper() }}</span>
                        @endif
                    </div>
                    @if ($item->biography)
                        <p class="m-quote">{{ $item->biography }}</p>
                    @endif
                </section>
                @if (is_array($item->qualifications) && count($item->qualifications))
                    <section data-reveal>
                        <p class="m-eyebrow">{{ __('Credentials') }}</p>
                        <h2>{{ __('Qualifications') }}</h2>
                        <ul class="m-steps-list">
                            @foreach ($item->qualifications as $qualification)
                                <li><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $qualification }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @elseif ($type === 'CaseStudyVersion')
                @foreach ([
                    'challenge' => __('Challenge'),
                    'approach' => __('Approach'),
                    'outcomes' => __('Results and outcomes'),
                ] as $field => $heading)
                    @if (filled(data_get($item, $field)))
                        <section data-reveal>
                            <p class="m-eyebrow">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} · {{ __('Evidence') }}</p>
                            <h2>{{ $heading }}</h2>
                            <p class="m-body">{{ data_get($item, $field) }}</p>
                        </section>
                    @endif
                @endforeach
            @elseif ($type === 'InsightVersion')
                <section data-reveal>
                    <p class="m-eyebrow">{{ __('Published insight') }}</p>
                    <div class="prose mt-6 max-w-none">
                        <p>{!! nl2br(e((string) $item->body)) !!}</p>
                    </div>
                </section>
            @else
                @foreach (['description' => __('Overview'), 'overview' => __('Overview'), 'body' => __('Details')] as $field => $heading)
                    @if (filled(data_get($item, $field)))
                        <section data-reveal>
                            <h2>{{ $heading }}</h2>
                            <p class="m-body">{{ data_get($item, $field) }}</p>
                        </section>
                    @endif
                @endforeach
            @endif
        </div>
    </article>

    <section class="m-section m-band" aria-labelledby="detail-next-title">
        <div class="content-container">
            <div class="m-center" data-reveal>
                <h2 id="detail-next-title" class="m-title">{{ __('Ready to move forward?') }}</h2>
                <p class="m-lead">{{ __('Tell us about the outcome you need. We will connect you with the relevant expertise.') }}</p>
                <div class="m-links">
                    <a class="m-btn" href="{{ route('consultation.create', $consultationParameters) }}">{{ __('Request advice') }}</a>
                    @if ($indexRoute)
                        <a class="m-link" href="{{ route($indexRoute, ['locale' => app()->getLocale()]) }}">{{ __('Browse all :type', ['type' => str($typeLabel)->lower()]) }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
