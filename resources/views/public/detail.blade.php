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
    $headerVariant = match ($type) {
        'InsightVersion' => 'knowledge',
        'ExpertVersion' => 'paper',
        default => 'standard',
    };
    $expertPhoto = $type === 'ExpertVersion' ? $item->expert?->profileMedia : null;
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
            :summary-override="data_get($item, 'summary') ?? data_get($item, 'excerpt') ?? data_get($item, 'professional_title')"
        />
    @else
    <x-ui.page-header
        :eyebrow="$typeLabel"
        :title="$pageTitle"
        :description="data_get($item, 'summary') ?? data_get($item, 'excerpt') ?? data_get($item, 'professional_title')"
        :variant="$headerVariant"
    >
        <a class="button-primary" href="{{ route('consultation.create', $consultationParameters) }}">{{ __('Request advice') }} →</a>
        @if ($indexRoute)
            <a class="button-secondary-dark" href="{{ route($indexRoute, ['locale' => app()->getLocale()]) }}">{{ __('Browse all :type', ['type' => str($typeLabel)->lower()]) }}</a>
        @endif
    </x-ui.page-header>
    @endif

    <div class="public-section impact-editorial-surface">
    <article class="public-detail-layout content-container">
        <div class="public-content-canvas reading-width">
            @if ($type === 'ServiceVersion')
                @if ($item->problem_statement)
                    <section class="grid gap-5 sm:grid-cols-[4rem_1fr]"><x-ui.editorial-number :number="1" /><div><p class="eyebrow">{{ __('Client challenges') }}</p><h2 class="heading-2">{{ __('The challenge this service addresses') }}</h2><p class="mt-5 text-base leading-8 text-ink">{{ $item->problem_statement }}</p></div></section>
                @endif
                @if ($item->approach)
                    <section class="mt-12 grid gap-5 border-t border-slate-300 pt-10 sm:grid-cols-[4rem_1fr]"><x-ui.editorial-number :number="2" /><div><p class="eyebrow">{{ __('Approach') }}</p><h2 class="heading-2">{{ __('How we work with you') }}</h2><p class="mt-5 whitespace-pre-line text-base leading-8 text-ink">{{ $item->approach }}</p></div></section>
                @endif
                @if (is_array($item->deliverables) && count($item->deliverables))
                    <section class="mt-12 border-t border-slate-300 pt-10"><p class="eyebrow">{{ __('Deliverables') }}</p><h2 class="heading-2">{{ __('What the engagement can produce') }}</h2><ol class="editorial-list mt-6">@foreach ($item->deliverables as $deliverable)<li class="editorial-list-item grid grid-cols-[3rem_1fr] gap-4 py-4"><x-ui.editorial-number :number="$loop->iteration" /><span>{{ $deliverable }}</span></li>@endforeach</ol></section>
                @endif
                @if ($item->benefits)
                    <section class="mt-12 border-t border-slate-200 pt-10"><p class="eyebrow">{{ __('Expected outcomes') }}</p><h2 class="heading-2">{{ __('The value we work toward') }}</h2><p class="mt-5 text-base leading-8 text-ink">{{ $item->benefits }}</p></section>
                @endif
            @elseif ($type === 'IndustryVersion')
                @if ($item->overview)<section><p class="eyebrow">{{ __('Sector context') }}</p><h2 class="heading-2">{{ __('Understanding the operating environment') }}</h2><p class="mt-5 whitespace-pre-line text-base leading-8">{{ $item->overview }}</p></section>@endif
                @if ($item->challenges)<section class="mt-12 border-t border-slate-200 pt-10"><p class="eyebrow">{{ __('Sector challenges') }}</p><h2 class="heading-2">{{ __('Issues shaping decisions and delivery') }}</h2><p class="mt-5 whitespace-pre-line text-base leading-8">{{ $item->challenges }}</p></section>@endif
            @elseif ($type === 'ExpertVersion')
                <section class="public-expert-detail-profile">
                    <div class="public-expert-detail-photo">
                        @if ($expertPhoto?->isPubliclyUsable())
                            <img
                                src="{{ $expertPhoto->publicUrl() }}"
                                alt="{{ $expertPhoto->alt_text ?: $item->display_name }}"
                                loading="eager"
                                decoding="async"
                            >
                        @else
                            <span aria-hidden="true">{{ str($item->display_name)->substr(0, 1)->upper() }}</span>
                        @endif
                    </div>
                    <div>
                        <p class="impact-quote mt-4 whitespace-pre-line text-lg leading-9">{{ $item->biography }}</p>
                    </div>
                </section>
                @if (is_array($item->qualifications) && count($item->qualifications))
                    <section class="mt-12 border-t border-slate-200 pt-10"><p class="eyebrow">{{ __('Credentials') }}</p><h2 class="heading-2">{{ __('Qualifications') }}</h2><ul class="mt-6 grid gap-3">@foreach($item->qualifications as $qualification)<li class="rounded-lg bg-quiet p-4">{{ $qualification }}</li>@endforeach</ul></section>
                @endif
            @elseif ($type === 'CaseStudyVersion')
                @foreach ([
                    'challenge' => __('Challenge'),
                    'approach' => __('Approach'),
                    'outcomes' => __('Results and outcomes'),
                ] as $field => $heading)
                    @if (filled(data_get($item, $field)))
                        <section @class(['grid gap-5 sm:grid-cols-[4rem_1fr]', 'mt-12 border-t border-slate-300 pt-10' => ! $loop->first])>
                            <x-ui.editorial-number :number="$loop->iteration" />
                            <div><p class="eyebrow">{{ __('Evidence') }}</p>
                            <h2 class="heading-2">{{ $heading }}</h2>
                            <p class="mt-5 whitespace-pre-line text-base leading-8">{{ data_get($item, $field) }}</p></div>
                        </section>
                    @endif
                @endforeach
            @elseif ($type === 'InsightVersion')
                <section>
                    <p class="eyebrow">{{ __('Published insight') }}</p>
                    <div class="prose prose-slate mt-6 max-w-none prose-headings:font-editorial prose-headings:text-brand-900 prose-a:text-action-700">
                        <p>{!! nl2br(e((string) $item->body)) !!}</p>
                    </div>
                </section>
            @else
                @foreach (['description' => __('Overview'), 'overview' => __('Overview'), 'body' => __('Details')] as $field => $heading)
                    @if (filled(data_get($item, $field)))
                        <section @class(['mt-12 border-t border-slate-200 pt-10' => ! $loop->first])><h2 class="heading-2">{{ $heading }}</h2><p class="mt-5 whitespace-pre-line text-base leading-8">{{ data_get($item, $field) }}</p></section>
                    @endif
                @endforeach
            @endif
        </div>

        <aside class="detail-context-panel">
            <p class="eyebrow">{{ __('At a glance') }}</p>
            <dl class="mt-5 divide-y divide-slate-200 text-sm">
                <div class="py-3"><dt class="font-bold text-brand-900">{{ __('Content type') }}</dt><dd class="mt-1 text-muted">{{ $typeLabel }}</dd></div>
                @if (filled(data_get($item, 'professional_title')))<div class="py-3"><dt class="font-bold text-brand-900">{{ __('Professional title') }}</dt><dd class="mt-1 text-muted">{{ $item->professional_title }}</dd></div>@endif
                @if (is_array(data_get($item, 'languages')) && count($item->languages))<div class="py-3"><dt class="font-bold text-brand-900">{{ __('Languages') }}</dt><dd class="mt-1 text-muted">{{ collect($item->languages)->join(', ') }}</dd></div>@endif
                @if (filled(data_get($item, 'updated_at')))<div class="py-3"><dt class="font-bold text-brand-900">{{ __('Updated') }}</dt><dd class="mt-1 text-muted"><time datetime="{{ $item->updated_at->toAtomString() }}">{{ $item->updated_at->locale(app()->getLocale())->translatedFormat('d M Y') }}</time></dd></div>@endif
            </dl>
            <div class="mt-6 border-t border-slate-200 pt-6">
                <h2 class="text-lg font-bold text-brand-900">{{ __('Ready to move forward?') }}</h2>
                <p class="mt-3 text-sm leading-6 text-muted">{{ __('Tell us about the outcome you need. We will connect you with the relevant expertise.') }}</p>
                <a class="button-primary mt-5 w-full" href="{{ route('consultation.create', $consultationParameters) }}">{{ __('Request advice') }}</a>
            </div>
        </aside>
    </article>
    </div>
@endsection
