@extends('layouts.public')

@php
    $pageHeader = collect($composition->sections)->first(
        fn ($section) => in_array($section->type->value, ['homepage_hero', 'page_header'], true),
    );
@endphp

@section('title', $pageHeader?->content['heading'] ?? __('Impact Consulting'))
@section('meta_description', $pageHeader?->content['summary'] ?? $publicExperience['seo']['default_description'])
@section('robots', $composition->preview ? 'noindex,nofollow,noarchive' : $publicExperience['seo']['robots'])

@section('content')
    <x-ui.page-composition :composition="$composition" />
@endsection
