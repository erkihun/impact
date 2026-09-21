@extends('layouts.public')

@section('title', __('About Impact Consulting'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        __('Home') => route('localized-home', ['locale' => app()->getLocale()]),
        __('About') => null,
    ]" />
@endsection

@section('content')
    @if ($managedComposition)
        <x-ui.page-composition :composition="$managedComposition" />
    @else
    <x-ui.page-header
        :eyebrow="__('About us')"
        :title="__('Independent thinking, rooted in context.')"
        :description="__('Impact Consulting brings together strategy, sector expertise and implementation discipline to help institutions make better decisions and sustain better results.')"
        variant="paper"
    >
        <a class="button-primary" href="{{ route('experts.index', ['locale' => app()->getLocale()]) }}">{{ __('Meet our experts') }} →</a>
        <a class="button-secondary" href="{{ route('case-studies.index', ['locale' => app()->getLocale()]) }}">{{ __('Review our work') }}</a>
    </x-ui.page-header>
    @endif

    <section class="public-section impact-editorial-surface" aria-labelledby="institutional-commitments-heading">
        <div class="public-section-grid content-container">
            <div class="public-section-rail">
                <x-ui.insight-marker :label="__('Institutional commitments')" />
                <h2 id="institutional-commitments-heading" class="public-section-rail-title">{{ __('How we earn confidence.') }}</h2>
                <p class="public-section-rail-copy">{{ __('Our advice is designed to remain useful after an engagement ends: grounded in evidence, candid about trade-offs and accountable to results.') }}</p>
            </div>

            <div class="public-register">
                @foreach ([
                    [__('Our purpose'), __('To strengthen the organizations and systems that improve lives and create shared prosperity.')],
                    [__('Our promise'), __('Clear advice, honest partnership and solutions that can work beyond the life of an engagement.')],
                    [__('Our standard'), __('Evidence-led, inclusive, accountable and uncompromising on ethics and confidentiality.')],
                ] as [$title, $copy])
                    <article class="public-register-row md:grid-cols-[5rem_15rem_minmax(0,1fr)]">
                        <x-ui.editorial-number :number="$loop->iteration" />
                        <h3 class="font-editorial text-xl font-bold text-brand-950">{{ $title }}</h3>
                        <p class="max-w-2xl leading-8 text-muted">{{ $copy }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
