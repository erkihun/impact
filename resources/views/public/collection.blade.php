@extends('layouts.public')

@section('title', $title . ' — ' . __('Impact Consulting'))

@php
    $isExpertCollection = str_starts_with($routePrefix, 'experts.');
    $locale = app()->getLocale();
@endphp

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        __('Home') => route('localized-home', ['locale' => $locale]),
        $title => null,
    ]" />
@endsection

@section('content')
    @if ($managedComposition)
        <x-ui.page-composition :composition="$managedComposition" />
    @else
        <x-ui.page-header :eyebrow="$eyebrow" :title="$title" :description="$description ?? null" />
    @endif

    <section class="m-section m-band" aria-label="{{ $title }}">
        <div class="content-container">
            <div @class(['m-collection', 'm-collection-people' => $isExpertCollection])>
                @forelse ($items as $item)
                    @php
                        $name = data_get($item, $nameField);
                        $summary = data_get($item, 'summary')
                            ?? data_get($item, 'excerpt')
                            ?? data_get($item, 'description')
                            ?? data_get($item, 'overview')
                            ?? data_get($item, 'biography');
                        $href = route($routePrefix, ['locale' => $locale, 'slug' => $item->slug]);
                        $expertPhoto = $isExpertCollection ? $item->expert?->profileMedia : null;
                        // A double-width lead card only when it leaves the three-column grid without gaps.
                        $featured = $loop->first && ! $isExpertCollection && $items->onFirstPage() && $items->count() >= 5 && ($items->count() - 2) % 3 === 0;
                    @endphp

                    @if ($isExpertCollection)
                        <article class="m-card m-person" style="--i: {{ $loop->index % 4 }}" data-reveal>
                            <div class="m-person-photo">
                                @if ($expertPhoto?->isPubliclyUsable())
                                    <img src="{{ $expertPhoto->publicUrl() }}" alt="{{ $expertPhoto->alt_text ?: $name }}" loading="lazy" decoding="async">
                                @else
                                    <span aria-hidden="true">{{ str($name)->substr(0, 1)->upper() }}</span>
                                @endif
                            </div>
                            <div class="m-person-body">
                                <h2><a href="{{ $href }}">{{ $name }}</a></h2>
                                @if (filled(data_get($item, 'professional_title')))
                                    <p>{{ $item->professional_title }}</p>
                                @endif
                                <span class="m-link mt-auto" aria-hidden="true">{{ __('View profile') }}</span>
                            </div>
                        </article>
                    @else
                        <article @class(['m-card', 'm-card-featured' => $featured]) style="--i: {{ $loop->index % 3 }}" data-reveal>
                            <div class="m-card-meta">
                                <span class="m-index">{{ str_pad((string) ($items->firstItem() + $loop->index), 2, '0', STR_PAD_LEFT) }}</span>
                                @if (isset($item->starts_at))
                                    <time datetime="{{ $item->starts_at?->toAtomString() }}">{{ $item->starts_at?->locale($locale)->translatedFormat('d M Y') }}</time>
                                @endif
                            </div>
                            <h2><a href="{{ $href }}">{{ $name }}</a></h2>
                            @if ($summary)
                                <p>{{ str($summary)->limit($featured ? 260 : 170) }}</p>
                            @endif
                            <span class="m-link" aria-hidden="true">{{ __('Learn more') }}</span>
                        </article>
                    @endif
                @empty
                    <x-ui.empty-state
                        class="border-0"
                        :title="__('No records are available yet.')"
                        :description="__('Contact our team if you need help finding the right information.')"
                    >
                        <a class="button-primary" href="{{ route('contact.create', ['locale' => $locale]) }}">{{ __('Choose a contact route') }}</a>
                    </x-ui.empty-state>
                @endforelse
            </div>

            @if ($items->hasPages())
                <nav class="m-pagination" aria-label="{{ __('Pagination') }}">
                    @if (! $items->onFirstPage())
                        <a class="button-secondary" href="{{ $items->previousPageUrl() }}">{{ __('Previous') }}</a>
                    @endif
                    @if ($items->hasMorePages())
                        <a class="button-primary" href="{{ $items->nextPageUrl() }}">{{ __('Next') }}</a>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection
