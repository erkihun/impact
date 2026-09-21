@extends('layouts.public')
@section('title', __('Page not found'))
@section('robots', 'noindex,follow')
@section('content')
    <x-ui.error-page code="404" :title="__('We could not find that page.')" :description="__('The address may be outdated, or the content may no longer be published. Use the navigation or return to the homepage.')" />
@endsection
