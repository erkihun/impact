@extends('layouts.public')
@section('title', __('Request conflict'))
@section('robots', 'noindex,nofollow')
@section('content')
    <x-ui.error-page code="409" :title="__('This request conflicts with the current state.')" :description="$message ?? __('Refresh the source page and try the action again.')" />
@endsection
