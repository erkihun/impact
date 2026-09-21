@extends('layouts.public')
@section('title', __('Too many requests'))
@section('robots', 'noindex,nofollow')
@section('content')
    <x-ui.error-page code="429" :title="__('Please wait before trying again.')" :description="__('We have temporarily limited requests from this connection. Nothing else is required; wait briefly, then retry.')" :show-recovery-routes="false" />
@endsection
