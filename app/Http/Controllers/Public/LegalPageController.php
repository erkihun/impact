<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Inertia\PublicPage;
use App\Support\Settings\PublicUiSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Response;

/**
 * Privacy, terms, cookie and accessibility pages. Their copy lives here, as
 * translated PHP arrays, so the React page receives localized text only.
 */
final class LegalPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $page = match ($request->route()?->getName()) {
            'legal.privacy' => 'privacy',
            'legal.terms' => 'terms',
            'legal.cookies' => 'cookies',
            default => 'accessibility',
        };
        $content = $this->{$page}();

        return PublicPage::render('Public/Legal', 'legal.'.$page, [
            'breadcrumbs' => PublicPage::breadcrumbs([$content['title'] => null]),
            'page' => $page,
            'header' => [
                'eyebrow' => $content['eyebrow'],
                'title' => $content['title'],
                'summary' => $content['summary'],
                'meta' => __('Last updated').': '.Carbon::parse(
                    app(PublicUiSettings::class)->viewData()['privacy']['policy_version'],
                )->isoFormat('LL'),
            ],
            'sections' => $content['sections'],
            'extra' => match ($page) {
                'cookies' => [
                    'id' => 'change-your-choices',
                    'heading' => __('Change your choices'),
                    'body' => __('You can review or change your privacy choices whenever you want. Your decision is recorded against the current version of this notice, and we will ask again if the notice changes materially.'),
                    'action' => ['type' => 'consent', 'label' => __('Manage privacy choices')],
                    'note' => __('You can also delete stored data at any time using your browser settings. Removing necessary storage may sign you out or reset your privacy choice.'),
                ],
                'accessibility' => [
                    'id' => 'report-a-barrier',
                    'heading' => __('Report an accessibility issue'),
                    'body' => __('Tell us what happened, which page you were on and what you were trying to do. If you can, include the browser and any assistive technology you were using. We will acknowledge your report and tell you what we intend to do about it.'),
                    'action' => ['type' => 'link', 'label' => __('Report an accessibility issue'), 'href' => route('contact.create')],
                    'note' => null,
                ],
                default => null,
            },
            'copy' => [
                'onThisPage' => __('On this page'),
                'questionsTitle' => __('Questions about this page?'),
                'questionsBody' => __('If anything here is unclear, or you want to exercise a right described above, contact us and we will respond.'),
                'contact' => __('Contact us'),
                'contactHref' => route('contact.create'),
            ],
        ], seo: PublicPage::seo('legal.'.$page, $content['title'], $content['description'] ?? $content['summary']));
    }

    /** @return array<string, mixed> */
    private function privacy(): array
    {
        return [
            'title' => __('Privacy notice'),
            'description' => __('How Impact Consulting collects, uses, shares and protects personal information, and the rights available to you.'),
            'eyebrow' => __('Legal and privacy'),
            'summary' => __('This notice explains what personal information we collect when you use this website, why we need it, how long we keep it and the choices you have.'),
            'sections' => [
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
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function terms(): array
    {
        return [
            'title' => __('Terms of use'),
            'description' => __('The terms that apply when you use the Impact Consulting website, including acceptable use, intellectual property and liability.'),
            'eyebrow' => __('Legal and privacy'),
            'summary' => __('These terms apply when you browse this website, submit an enquiry or proposal, or apply for a role. Please read them before using the site.'),
            'sections' => [
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
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function cookies(): array
    {
        return [
            'title' => __('Cookie notice'),
            'description' => __('The cookies and similar storage this website uses, what each purpose does and how to change your choices.'),
            'eyebrow' => __('Legal and privacy'),
            'summary' => __('This page explains the cookies and similar storage we use, what each purpose is for and how you can change your decision at any time.'),
            'sections' => [
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
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function accessibility(): array
    {
        return [
            'title' => __('Accessibility statement'),
            'description' => __('Our accessibility commitment, the standard we work to, known limitations and how to report a barrier you encounter.'),
            'eyebrow' => __('Accessibility'),
            'summary' => __('We want everyone to be able to read our work and reach us. This statement explains the standard we design and build to, what we know is not yet perfect and how to tell us when something does not work for you.'),
            'sections' => [
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
                        __('Content is published in English, with the page language identified for assistive technology.'),
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
            ],
        ];
    }
}
