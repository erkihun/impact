@extends('layouts.public')

@section('title', __('Search — Impact Consulting'))
@section('robots', 'noindex,follow')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        __('Home') => route('localized-home', ['locale' => app()->getLocale()]),
        __('Search') => null,
    ]" />
@endsection

@section('content')
    <x-ui.page-header
        :eyebrow="__('Knowledge discovery')"
        :title="__('Search')"
        :description="__('Find published services, sector experience, experts, evidence, insights, events and opportunities.')"
        variant="knowledge"
    />

    <section class="public-section impact-editorial-surface" aria-labelledby="search-results-heading">
        <div class="content-container">
            <x-ui.search-form
                class="public-filter-panel"
                :action="route('search', ['locale' => app()->getLocale()])"
                :query="$query"
                id="site-search-query"
            />

            <div class="public-section-grid mt-10 sm:mt-12">
                <div class="public-section-rail">
                    <x-ui.insight-marker :label="__('Knowledge discovery')" />
                    <h2 id="search-results-heading" class="public-section-rail-title">{{ __('Search results') }}</h2>
                    <x-ui.result-count class="mt-6" :count="$results->total()" :query="$query" />
                </div>

                <div>
                    <div class="public-register">
                        @forelse ($results as $result)
                            <x-ui.search-result
                                :result="$result"
                                :number="$results->firstItem() + $loop->index"
                            />
                        @empty
                            <x-ui.empty-state
                                class="border-0"
                                :title="filled($query) ? __('No results matched “:query”.', ['query' => $query]) : __('Start with a topic, service or name.')"
                                :description="__('Try fewer words or use one of the trusted routes below to continue.')"
                            >
                                @if (filled($query))
                                    <a class="button-secondary" href="{{ route('search', ['locale' => app()->getLocale()]) }}">{{ __('Clear search') }}</a>
                                @endif
                                <a class="button-primary" href="{{ route('services.index', ['locale' => app()->getLocale()]) }}">{{ __('Explore services') }}</a>
                                <a class="button-secondary" href="{{ route('insights.index', ['locale' => app()->getLocale()]) }}">{{ __('Browse insights') }}</a>
                            </x-ui.empty-state>
                        @endforelse
                    </div>

                    @if ($results->hasPages())
                        <nav class="mt-12" aria-label="{{ __('Pagination') }}">{{ $results->links() }}</nav>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
