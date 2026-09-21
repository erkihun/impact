@extends('layouts.public')
@section('title', __('Page expired'))
@section('robots', 'noindex,nofollow')
@section('content')
    <x-ui.error-page code="419" :title="__('Your secure session has expired.')" :description="__('For your protection, the form can no longer be submitted from this page. Open the form again and re-enter the information.')" :action-label="__('Return to the homepage')" />
@endsection
