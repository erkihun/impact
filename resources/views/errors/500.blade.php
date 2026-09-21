@extends('layouts.public')
@section('title', __('Service error'))
@section('robots', 'noindex,nofollow')
@section('content')
    <x-ui.error-page code="500" :title="__('We could not complete that request.')" :description="__('The issue has not changed any confirmation you already received. Try again later, or contact us if the problem continues.')" />
@endsection
