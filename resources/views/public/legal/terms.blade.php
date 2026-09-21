@extends('layouts.public')

@section('title', __('Terms of use'))
@section('meta_description', __('The terms that apply when you use the Impact Consulting website, including acceptable use, intellectual property and liability.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[__('Home') => route('localized-home', ['locale' => app()->getLocale()]), __('Terms of use') => null]" />
@endsection

@section('content')
    <x-ui.legal-page
        :composition="$managedComposition"
        :eyebrow="__('Legal and privacy')"
        :title="__('Terms of use')"
        :summary="__('These terms apply when you browse this website, submit an enquiry or proposal, or apply for a role. Please read them before using the site.')"
        :updated="$publicExperience['privacy']['policy_version']"
        :sections="[
            [
                'id' => 'acceptance',
                'heading' => __('Using this site'),
                'body' => [__('By using this website you accept these terms. If you do not accept them, please do not use the site. We may update these terms and will show the date of the most recent change on this page.')],
            ],
            [
                'id' => 'acceptable-use',
                'heading' => __('Acceptable use'),
                'body' => [__('You may browse this site, and submit information through our forms, for legitimate business purposes. You must not:')],
                'list' => [
                    __('Attempt to gain unauthorized access to any part of the site, its systems or its data.'),
                    __('Upload files that contain malicious code or that you do not have the right to share.'),
                    __('Submit information that is false, misleading or that infringes the rights of another person.'),
                    __('Use automated tools to extract content at a scale that affects availability for others.'),
                ],
            ],
            [
                'id' => 'submissions',
                'heading' => __('Information you submit'),
                'body' => [
                    __('When you send an enquiry, proposal or application you confirm that you are entitled to share the information and that it is accurate as far as you know.'),
                    __('Submitting a request does not create a contract or an engagement. Any advisory relationship begins only under a separate signed agreement.'),
                ],
            ],
            [
                'id' => 'intellectual-property',
                'heading' => __('Intellectual property'),
                'body' => [__('The content on this site, including text, reports, graphics and our name and marks, belongs to us or to our licensors. You may read, quote and share our published insights with clear attribution. You may not republish substantial parts of them as your own work or for commercial resale without our written permission.')],
            ],
            [
                'id' => 'content-accuracy',
                'heading' => __('Accuracy of published content'),
                'body' => [__('Our insights, reports and case studies are published for general information. They describe work carried out in a specific context at a specific time and are not advice for your situation. You should obtain professional advice before acting on anything published here.')],
            ],
            [
                'id' => 'third-party-links',
                'heading' => __('Links to other sites'),
                'body' => [__('Where we link to another organization we do so for convenience. We do not control those sites and are not responsible for their content, availability or privacy practices.')],
            ],
            [
                'id' => 'availability',
                'heading' => __('Availability'),
                'body' => [__('We aim to keep this site available and accurate, but we may suspend or withdraw part of it for maintenance or operational reasons. Where we can anticipate an interruption, we will say what is affected and for how long.')],
            ],
            [
                'id' => 'liability',
                'heading' => __('Liability'),
                'body' => [__('To the extent permitted by law, we are not liable for loss arising from reliance on general content published on this site. Nothing in these terms limits liability that cannot be limited by law.')],
            ],
        ]"
    />
@endsection
