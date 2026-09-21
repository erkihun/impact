@extends('layouts.public')
@section('title', __('Access denied'))
@section('robots', 'noindex,nofollow')
@section('content')
    <x-ui.error-page code="403" :title="__('You do not have access to this page.')" :description="__('Your account may not have the required permission. Return to a page available to you or contact an administrator if you believe this is incorrect.')" />
@endsection
