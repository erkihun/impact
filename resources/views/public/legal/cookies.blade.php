@extends('layouts.public')

@section('title', __('Cookie notice'))
@section('meta_description', __('The cookies and similar storage this website uses, what each purpose does and how to change your choices.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[__('Home') => route('localized-home', ['locale' => app()->getLocale()]), __('Cookie notice') => null]" />
@endsection

@section('content')
    <x-ui.legal-page
        :composition="$managedComposition"
        :eyebrow="__('Legal and privacy')"
        :title="__('Cookie notice')"
        :summary="__('This page explains the cookies and similar storage we use, what each purpose is for and how you can change your decision at any time.')"
        :updated="$publicExperience['privacy']['policy_version']"
        :sections="[
            [
                'id' => 'how-we-use-storage',
                'heading' => __('How we use storage'),
                'body' => [
                    __('Cookies and similar storage are small pieces of data saved by your browser. We group them by purpose so that you can decide about each one separately.'),
                    __('Only necessary storage is set when you arrive. Nothing optional is stored until you agree to it.'),
                ],
            ],
            [
                'id' => 'necessary',
                'heading' => __('Necessary'),
                'body' => [__('Required for the site to work. This storage keeps your session secure, protects our forms against automated misuse and remembers the privacy choice you made so that we do not ask you again on every page.')],
            ],
            [
                'id' => 'preferences',
                'heading' => __('Preferences'),
                'body' => [__('Optional. Remembers choices such as your selected language so that you do not have to set them again on your next visit.')],
            ],
            [
                'id' => 'analytics',
                'heading' => __('Analytics'),
                'body' => [__('Optional. Helps us understand which services, insights and journeys people find useful so that we can improve them. We do not record the text you type into forms or the documents you upload.')],
            ],
            [
                'id' => 'marketing',
                'heading' => __('Marketing'),
                'body' => [__('Optional. Helps us understand whether our campaigns and newsletter reach the right audiences.')],
            ],
        ]"
    >
        <section id="change-your-choices" class="mt-12 scroll-mt-28 border-t border-slate-300 pt-8">
            <h2 class="heading-3">{{ __('Change your choices') }}</h2>
            <p class="mt-4 text-base leading-8 text-slate-700">{{ __('You can review or change your privacy choices whenever you want. Your decision is recorded against the current version of this notice, and we will ask again if the notice changes materially.') }}</p>
            <button type="button" class="button-primary mt-5" data-open-consent-preferences>{{ __('Manage privacy choices') }}</button>
            <p class="mt-4 text-sm leading-6 text-muted">{{ __('You can also delete stored data at any time using your browser settings. Removing necessary storage may sign you out or reset your privacy choice.') }}</p>
        </section>
    </x-ui.legal-page>
@endsection
