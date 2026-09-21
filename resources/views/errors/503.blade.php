@extends('layouts.public')
@section('title', __('Service temporarily unavailable'))
@section('robots', 'noindex,nofollow')
@section('content')
    <x-ui.error-page code="503" :title="__('This service is temporarily unavailable.')" :description="__('We are carrying out maintenance or recovering service. Please return later.')" :show-recovery-routes="false" />
@endsection
