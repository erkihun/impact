@extends('layouts.public')

@section('title', __('Privacy notice'))
@section('meta_description', __('How Impact Consulting collects, uses, shares and protects personal information, and the rights available to you.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[__('Home') => route('localized-home', ['locale' => app()->getLocale()]), __('Privacy notice') => null]" />
@endsection

@section('content')
    <x-ui.legal-page
        :composition="$managedComposition"
        :eyebrow="__('Legal and privacy')"
        :title="__('Privacy notice')"
        :summary="__('This notice explains what personal information we collect when you use this website, why we need it, how long we keep it and the choices you have.')"
        :updated="$publicExperience['privacy']['policy_version']"
        :sections="[
            [
                'id' => 'summary',
                'heading' => __('In summary'),
                'body' => [__('We collect only the information we need to answer your request, deliver a service you asked for or meet a legal obligation. We do not sell personal information.')],
                'list' => [
                    __('We ask for contact details so that we can reply to your enquiry.'),
                    __('Optional analytics and marketing storage is used only if you agree to it.'),
                    __('You can ask us for a copy of your information, or ask us to correct or delete it.'),
                ],
            ],
            [
                'id' => 'information-we-collect',
                'heading' => __('Information we collect'),
                'body' => [__('The information we hold depends on how you interact with us.')],
                'list' => [
                    __('Enquiry and consultation details: your name, organization, role, contact details and the challenge you describe.'),
                    __('Proposal submissions: project information and any documents you choose to upload.'),
                    __('Job applications: the details and files you provide in support of an application.'),
                    __('Newsletter subscriptions: your email address and your recorded consent.'),
                    __('Technical information: security and performance logs needed to operate the site safely.'),
                ],
            ],
            [
                'id' => 'why-we-use-it',
                'heading' => __('Why we use your information'),
                'list' => [
                    __('To route your request to the right team and respond to it.'),
                    __('To assess an application or proposal you have submitted.'),
                    __('To send the newsletter where you have asked us to.'),
                    __('To keep the site secure and to prevent misuse of our forms.'),
                    __('To meet accounting, regulatory and record-keeping obligations.'),
                ],
            ],
            [
                'id' => 'cookies-and-storage',
                'heading' => __('Cookies and similar storage'),
                'body' => [
                    __('Necessary storage keeps your session secure, protects our forms and remembers your privacy choice. It cannot be switched off.'),
                    __('Preferences, analytics and marketing storage is optional and is not set before you agree to it. You can change your decision at any time using the privacy choices link in the footer.'),
                ],
            ],
            [
                'id' => 'sharing',
                'heading' => __('When we share information'),
                'body' => [__('We share personal information only where it is necessary, and we require anyone acting on our behalf to protect it.')],
                'list' => [
                    __('With service providers who host, secure or support this platform under contract.'),
                    __('With a client organization where you have asked us to make an introduction.'),
                    __('Where we are required to by law, regulation or a valid legal request.'),
                ],
            ],
            [
                'id' => 'retention',
                'heading' => __('How long we keep it'),
                'body' => [__('We keep information only as long as it is needed for the purpose it was collected for, or as long as the law requires. Engagement and application records follow our published retention schedule, after which they are deleted or anonymized.')],
            ],
            [
                'id' => 'your-rights',
                'heading' => __('Your rights'),
                'body' => [__('Subject to applicable law, you can ask us to do the following. We will confirm your identity before acting on a request.')],
                'list' => [
                    __('Give you a copy of the personal information we hold about you.'),
                    __('Correct information that is inaccurate or incomplete.'),
                    __('Delete information where we no longer have a valid reason to keep it.'),
                    __('Stop sending you marketing communications at any time.'),
                    __('Withdraw a consent you previously gave, without affecting earlier lawful use.'),
                ],
            ],
            [
                'id' => 'security',
                'heading' => __('How we protect information'),
                'body' => [__('Access to submitted information is restricted to authorized staff. Uploaded files are scanned and stored in controlled storage rather than served publicly, and access to sensitive records is logged.')],
            ],
        ]"
    />
@endsection
