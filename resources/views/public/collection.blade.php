@extends('layouts.public')

@section('title', $title . ' — ' . __('Impact Consulting'))

@php
    $headerVariant = str_starts_with($routePrefix, 'insights.') ? 'knowledge' : (str_starts_with($routePrefix, 'experts.') ? 'paper' : 'standard');
    $isExpertCollection = str_starts_with($routePrefix, 'experts.');
@endphp

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        __('Home') => route('localized-home', ['locale' => app()->getLocale()]),
        $title => null,
    ]" />
@endsection

@section('content')
    @if ($managedComposition)
        <x-ui.page-composition :composition="$managedComposition" />
    @else
        <x-ui.page-header :eyebrow="$eyebrow" :title="$title" :description="$description ?? null" :variant="$headerVariant" />
    @endif

    <section class="public-section impact-editorial-surface" aria-label="{{ $title }}">
        <div class="content-container">
            <div class="public-collection-heading">
                <x-ui.insight-marker :label="$eyebrow" />
            </div>

            <div @class([
                'public-collection-grid',
                'public-expert-grid' => $isExpertCollection,
            ])>
                @forelse ($items as $item)
                    @php
                        $name = data_get($item, $nameField);
                        $summary = data_get($item, 'summary')
                            ?? data_get($item, 'excerpt')
                            ?? data_get($item, 'description')
                            ?? data_get($item, 'overview')
                            ?? data_get($item, 'biography');
                        $expertPhoto = $isExpertCollection ? $item->expert?->profileMedia : null;
                    @endphp
                    <article @class([
                        'group public-collection-card',
                        'public-collection-card-featured' => $loop->first && ! $isExpertCollection,
                        'public-expert-card' => $isExpertCollection,
                    ])>
                        @if ($isExpertCollection)
                            <div class="public-expert-photo">
                                @if ($expertPhoto?->isPubliclyUsable())
                                    <img
                                        src="{{ $expertPhoto->publicUrl() }}"
                                        alt="{{ $expertPhoto->alt_text ?: $name }}"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                @else
                                    <span aria-hidden="true">{{ str($name)->substr(0, 1)->upper() }}</span>
                                @endif
                            </div>
                        @else
                            <div class="public-collection-card-meta">
                                <span class="public-collection-index">{{ str_pad((string) ($items->firstItem() + $loop->index), 2, '0', STR_PAD_LEFT) }}</span>
                                @if (isset($item->starts_at))
                                    <time class="public-collection-date" datetime="{{ $item->starts_at?->toAtomString() }}">{{ $item->starts_at?->locale(app()->getLocale())->translatedFormat('d M Y') }}</time>
                                @endif
                            </div>
                        @endif
                        <div class="min-w-0">
                            @unless ($isExpertCollection)
                                <p class="eyebrow">{{ str(class_basename($item))->replace('Version', '')->headline() }}</p>
                            @endunless
                            <h3 @class([
                                'font-editorial font-black leading-tight text-brand-950',
                                'mt-3' => ! $isExpertCollection,
                                'text-2xl sm:text-3xl' => $loop->first && ! $isExpertCollection,
                                'text-xl' => ! $loop->first || $isExpertCollection,
                            ])>
                                <a class="group-hover:text-action-700" href="{{ route($routePrefix, ['locale' => app()->getLocale(), 'slug' => $item->slug]) }}">{{ $name }}</a>
                            </h3>
                            @if (filled(data_get($item, 'professional_title')))<p class="mt-2 text-sm font-semibold text-knowledge-700">{{ $item->professional_title }}</p>@endif
                            @if ($summary)<p class="mt-4 max-w-3xl text-sm leading-7 text-muted">{{ str($summary)->limit($isExpertCollection ? 190 : ($loop->first ? 340 : 240)) }}</p>@endif
                            @if ($isExpertCollection)
                                <a class="public-expert-profile-link" href="{{ route($routePrefix, ['locale' => app()->getLocale(), 'slug' => $item->slug]) }}">
                                    {{ __('View profile') }} <span aria-hidden="true">&rarr;</span>
                                </a>
                            @endif
                        </div>
                        @unless ($isExpertCollection)
                            <span class="public-collection-arrow" aria-hidden="true">&rarr;</span>
                        @endunless
                    </article>
                @empty
                    <x-ui.empty-state
                        class="border-0"
                        :title="__('No records are available yet.')"
                        :description="__('Contact our team if you need help finding the right information.')"
                    >
                        <a class="button-primary" href="{{ route('contact.create', ['locale' => app()->getLocale()]) }}">{{ __('Choose a contact route') }}</a>
                    </x-ui.empty-state>
                @endforelse
            </div>

            @if ($items->hasPages())
                @if ($isExpertCollection)
                    <nav class="public-expert-pagination" aria-label="{{ __('Pagination') }}">
                        @if (! $items->onFirstPage())
                            <a class="button-secondary" href="{{ $items->previousPageUrl() }}">{{ __('Previous') }}</a>
                        @endif
                        @if ($items->hasMorePages())
                            <a class="button-primary" href="{{ $items->nextPageUrl() }}">{{ __('Next') }}</a>
                        @endif
                    </nav>
                @else
                    <nav class="mt-12" aria-label="{{ __('Pagination') }}">{{ $items->links() }}</nav>
                @endif
            @endif
        </div>
    </section>
@endsection
