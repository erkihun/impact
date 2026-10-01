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
        >
            <a class="button-primary" href="{{ route('experts.index', ['locale' => app()->getLocale()]) }}">{{ __('Meet our experts') }}</a>
            <a class="button-secondary" href="{{ route('case-studies.index', ['locale' => app()->getLocale()]) }}">{{ __('Review our work') }}</a>
        </x-ui.page-header>
    @endif

    <section class="m-section m-band" aria-labelledby="institutional-commitments-heading">
        <div class="content-container">
            <div class="m-center" data-reveal>
                <p class="m-eyebrow">{{ __('Institutional commitments') }}</p>
                <h2 id="institutional-commitments-heading" class="m-title">{{ __('How we earn confidence.') }}</h2>
                <p class="m-lead">{{ __('Our advice is designed to remain useful after an engagement ends: grounded in evidence, candid about trade-offs and accountable to results.') }}</p>
            </div>

            <div class="m-grid m-grid-3 mt-14">
                @foreach ([
                    [__('Our purpose'), __('To strengthen the organizations and systems that improve lives and create shared prosperity.')],
                    [__('Our promise'), __('Clear advice, honest partnership and solutions that can work beyond the life of an engagement.')],
                    [__('Our standard'), __('Evidence-led, inclusive, accountable and uncompromising on ethics and confidentiality.')],
                ] as [$title, $copy])
                    <article class="m-tile" style="--i: {{ $loop->index }}" data-reveal>
                        <span class="m-index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3>{{ $title }}</h3>
                        <p>{{ $copy }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="m-section" aria-labelledby="about-next-title">
        <div class="content-container">
            <div class="m-center" data-reveal>
                <h2 id="about-next-title" class="m-title">{{ __('Bring the question. We will help structure the next move.') }}</h2>
                <div class="m-links">
                    <a class="m-btn" href="{{ route('consultation.create', ['locale' => app()->getLocale()]) }}">{{ __('Request a consultation') }}</a>
                    <a class="m-link" href="{{ route('experts.index', ['locale' => app()->getLocale()]) }}">{{ __('Meet our experts') }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection
