@extends('layouts.public')

@section('title', __('Preview: :title', ['title' => $version->title]))
@section('robots', 'noindex,nofollow,noarchive')

@section('content')
    <div class="status-warning rounded-none border-x-0 border-t-0">
        <div class="site-shell py-3 text-sm font-semibold">
            {{ __('Private preview. Version :version. This page is not published.', ['version' => $version->version_no]) }}
        </div>
    </div>
    <article class="site-shell py-16">
        <p class="text-sm font-bold uppercase tracking-wide text-impact-700">{{ str($content->type->value)->headline() }}</p>
        <h1 class="mt-3 max-w-4xl font-serif text-4xl font-bold">{{ $version->title }}</h1>
        @if ($version->summary)
            <p class="mt-6 max-w-3xl text-xl leading-8 text-slate-600">{{ $version->summary }}</p>
        @endif
        <div class="prose mt-10 max-w-3xl">{{ data_get($version->body, 'content') }}</div>
    </article>
@endsection
