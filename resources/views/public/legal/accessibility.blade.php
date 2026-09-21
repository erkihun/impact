@extends('layouts.public')

@section('title', __('Accessibility statement'))
@section('meta_description', __('Our accessibility commitment, the standard we work to, known limitations and how to report a barrier you encounter.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[__('Home') => route('localized-home', ['locale' => app()->getLocale()]), __('Accessibility statement') => null]" />
@endsection

@section('content')
    <x-ui.legal-page
        :composition="$managedComposition"
        :eyebrow="__('Accessibility')"
        :title="__('Accessibility statement')"
        :summary="__('We want everyone to be able to read our work and reach us. This statement explains the standard we design and build to, what we know is not yet perfect and how to tell us when something does not work for you.')"
        :updated="$publicExperience['privacy']['policy_version']"
        :sections="[
            [
                'id' => 'commitment',
                'heading' => __('Our commitment'),
                'body' => [__('We treat accessibility as a design requirement rather than a final check. Keyboard access, contrast, focus visibility, content order and clear language are considered from the start of each screen we build.')],
            ],
            [
                'id' => 'standard',
                'heading' => __('The standard we work to'),
                'body' => [__('We aim to meet the Web Content Accessibility Guidelines (WCAG) version 2.2 at Level AA across both the public site and our authenticated screens.')],
            ],
            [
                'id' => 'what-we-do',
                'heading' => __('What this means in practice'),
                'list' => [
                    __('Every interactive element can be reached and operated with a keyboard, in a logical order, with a visible focus indicator.'),
                    __('Menus, dialogs and accordions follow established keyboard patterns and return focus where you expect it.'),
                    __('Colour is never the only way we communicate status, selection or an error.'),
                    __('Form labels, instructions and error messages are connected to their fields so that screen readers announce them.'),
                    __('Pages remain usable at 200 percent zoom and reflow to narrow screens without loss of information.'),
                    __('Informative images have meaningful alternative text, and animation respects a reduced-motion preference.'),
                    __('Content is published in English and Amharic, with the page language identified for assistive technology.'),
                ],
            ],
            [
                'id' => 'how-we-test',
                'heading' => __('How we test'),
                'body' => [__('We combine automated checks with manual keyboard testing, screen-reader testing, contrast review and responsive verification. Automated tools alone cannot confirm accessibility, so a human review is part of every release.')],
            ],
            [
                'id' => 'known-limitations',
                'heading' => __('Known limitations'),
                'body' => [__('We publish what we know rather than claiming full conformance. We are currently working on the following areas:')],
                'list' => [
                    __('Some documents published before this platform launched were not created with an accessible structure. Tell us which document you need and we will provide an accessible version or the content in another format.'),
                    __('Independent assessment with assistive technology across all journeys is in progress, and findings will be corrected as they are confirmed.'),
                    __('Where a third-party feature is embedded, we may not control its accessibility. We will provide an equivalent route where that happens.'),
                ],
            ],
            [
                'id' => 'alternatives',
                'heading' => __('If something is not accessible to you'),
                'body' => [__('You do not have to use this website to reach us. If a page, form or document is a barrier, contact us and we will provide the information or take your request another way. We will not ask you to explain why you need an alternative.')],
            ],
        ]"
    >
        <section id="report-a-barrier" class="mt-12 scroll-mt-28 border-t border-slate-300 pt-8">
            <h2 class="heading-3">{{ __('Report an accessibility issue') }}</h2>
            <p class="mt-4 text-base leading-8 text-slate-700">{{ __('Tell us what happened, which page you were on and what you were trying to do. If you can, include the browser and any assistive technology you were using. We will acknowledge your report and tell you what we intend to do about it.') }}</p>
            <a class="button-primary mt-5" href="{{ route('contact.create', ['locale' => app()->getLocale()]) }}">{{ __('Report an accessibility issue') }}</a>
        </section>
    </x-ui.legal-page>
@endsection
