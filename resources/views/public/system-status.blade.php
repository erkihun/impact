@extends('layouts.public')

@section('title', $title)

@section('content')
    <x-ui.page-header
        :eyebrow="__('Service information')"
        :title="$title"
        :description="$message"
        variant="paper"
    />

    <section class="public-section impact-editorial-surface">
        <div class="content-container">
        <div class="mx-auto max-w-3xl border-t-4 border-t-action-500 bg-white p-6 shadow-editorial sm:p-10">
            <div @class(['mt-8', 'status-warning' => $bannerActive, 'status-success' => ! $bannerActive])>
                {{ $bannerActive
                    ? __('A scheduled maintenance notice is currently active.')
                    : __('No scheduled maintenance notice is currently active.') }}
            </div>

            @if ($supportUrl)
                <a class="button-primary mt-8" href="{{ $supportUrl }}">{{ __('Contact support') }}</a>
            @endif
        </div>
        </div>
    </section>
@endsection
