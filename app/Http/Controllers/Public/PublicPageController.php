<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoMetadataBuilder;
use App\Support\Inertia\PublicPage;
use App\Support\Settings\PublicUiSettings;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * About and the three engagement intake pages. Forms are described as
 * translated schemas; one accessible React form renders all of them.
 */
final class PublicPageController extends Controller
{
    public function about(): Response
    {

        return PublicPage::render('Public/About', 'about', [
            'breadcrumbs' => PublicPage::breadcrumbs([__('About') => null]),
            'header' => [
                'eyebrow' => __('About us'),
                'title' => __('Independent thinking, rooted in context.'),
                'summary' => __('Impact Consulting brings together strategy, sector expertise and implementation discipline to help institutions make better decisions and sustain better results.'),
            ],
            'principles' => [
                ['title' => __('Our purpose'), 'text' => __('To strengthen the organizations and systems that improve lives and create shared prosperity.')],
                ['title' => __('Our promise'), 'text' => __('Clear advice, honest partnership and solutions that can work beyond the life of an engagement.')],
                ['title' => __('Our standard'), 'text' => __('Evidence-led, inclusive, accountable and uncompromising on ethics and confidentiality.')],
            ],
            'links' => [
                'experts' => route('experts.index'),
                'caseStudies' => route('case-studies.index'),
                'consultation' => route('consultation.create'),
            ],
            'copy' => [
                'meetExperts' => __('Meet our experts'),
                'reviewWork' => __('Review our work'),
                'commitmentsEyebrow' => __('Institutional commitments'),
                'commitmentsTitle' => __('How we earn confidence.'),
                'commitmentsLead' => __('Our advice is designed to remain useful after an engagement ends: grounded in evidence, candid about trade-offs and accountable to results.'),
                'nextTitle' => __('Bring the question. We will help structure the next move.'),
                'requestConsultation' => __('Request a consultation'),
            ],
        ], seo: PublicPage::seo(
            'about',
            __('About Impact Consulting'),
            __('Impact Consulting brings together strategy, sector expertise and implementation discipline to help institutions make better decisions and sustain better results.'),
            'AboutPage',
        ));
    }

    public function consultation(Request $request): Response
    {
        $hasContext = $request->filled('service_id') || $request->filled('industry_id');

        return $this->engagement('consultation', PublicPage::seo(
            'consultation',
            __('Request a consultation'),
            __('Describe the outcome you need, your context and timing. Impact Consulting reviews each request and connects you with the relevant advisory expertise.'),
            'ContactPage',
        ), [
            'breadcrumbs' => PublicPage::breadcrumbs([__('Request a consultation') => null]),
            'header' => [
                'eyebrow' => __('Start a conversation'),
                'title' => __('Tell us what success needs to look like.'),
                'summary' => __('Share enough context for us to route your request. A member of our team will respond using the contact details you provide.'),
            ],
            'form' => [
                'id' => 'consultation',
                'action' => route('consultation-requests.store'),
                'multipart' => false,
                'hidden' => array_filter([
                    'type' => 'consultation',
                    'policy_version' => $this->policyVersion(),
                    'service_id' => $request->query('service_id'),
                    'industry_id' => $request->query('industry_id'),
                ], fn ($value): bool => filled($value)),
                'intro' => __('Fields marked required must be completed. We request only information needed to route and respond to your inquiry.'),
                'progressLabel' => __('Consultation request progress'),
                'submitLabel' => __('Send consultation request'),
                'steps' => [
                    [
                        'label' => __('Need'),
                        'eyebrow' => __('Step 1 of 4'),
                        'heading' => __('What outcome or challenge should we understand?'),
                        'notice' => $hasContext ? __('This request includes context selected from the page you were viewing. You can still describe a different need below.') : null,
                        'fields' => [
                            $this->field('description', __('Challenge summary'), 'textarea', required: true, help: __('Describe the decision, problem or result you want help with. You do not need to know the internal service name.'), minlength: 20, maxlength: 10000, wide: true),
                        ],
                    ],
                    [
                        'label' => __('Organization'),
                        'eyebrow' => __('Step 2 of 4'),
                        'heading' => __('Who should we respond to?'),
                        'fields' => $this->contactFields(withPhone: true),
                    ],
                    [
                        'label' => __('Project'),
                        'eyebrow' => __('Step 3 of 4'),
                        'heading' => __('What timing and scope should we consider?'),
                        'fields' => [
                            $this->field('timeframe', __('Timeframe'), help: __('For example: next quarter or before a stated decision date.'), maxlength: 100),
                            $this->field('budget_range', __('Indicative budget'), help: __('A range is optional and helps us suggest an appropriate response.'), maxlength: 100),
                        ],
                    ],
                    [
                        'label' => __('Review'),
                        'eyebrow' => __('Step 4 of 4'),
                        'heading' => __('Review and send your request'),
                        'review' => $this->review(__('Challenge summary')),
                        'consent' => __('I understand that Impact Consulting will use these details to assess and respond to this request under the current privacy notice.'),
                        'note' => __('After submission, you will receive an authoritative reference and an explanation of what happens next.'),
                    ],
                ],
            ],
            'context' => [
                'eyebrow' => __('Before you begin'),
                'title' => __('The clearest requests start with the decision.'),
                'text' => __('You do not need to diagnose the solution. Tell us what must change, who is affected and when the decision matters.'),
                'items' => [__('The outcome or decision'), __('The operating context'), __('The people involved'), __('The relevant timing')],
                'footerTitle' => __('What happens next'),
                'footerText' => __('We review the context, identify the relevant expertise and respond using the details you provide.'),
                'links' => [],
            ],
        ]);
    }

    public function rfp(): Response
    {

        return $this->engagement('rfp', PublicPage::seo(
            'rfp',
            __('Submit a request for proposal'),
            __('Share a confidential request for proposal with Impact Consulting. Describe the assignment, expected outcomes and timing; supporting files stay private.'),
            'ContactPage',
        ), [
            'breadcrumbs' => PublicPage::breadcrumbs([__('Submit an RFP') => null]),
            'header' => [
                'eyebrow' => __('Request for proposal'),
                'title' => __('Share your brief securely.'),
                'summary' => __('Tell us about the assignment, expected outcomes and timing. Supporting files remain private and unavailable until security processing is complete.'),
            ],
            'form' => [
                'id' => 'rfp',
                'action' => route('rfp-requests.store'),
                'multipart' => true,
                'hidden' => ['type' => 'rfp', 'policy_version' => $this->policyVersion()],
                'intro' => __('Fields marked required must be completed. Do not include credentials, passwords or unnecessary personal data.'),
                'progressLabel' => __('RFP submission progress'),
                'submitLabel' => __('Submit RFP'),
                'steps' => [
                    [
                        'label' => __('Need'),
                        'eyebrow' => __('Step 1 of 4'),
                        'heading' => __('What assignment should the proposal address?'),
                        'fields' => [
                            $this->field('description', __('Assignment description'), 'textarea', required: true, help: __('Describe the context, desired outcomes, expected deliverables and any material constraints.'), minlength: 20, maxlength: 10000, wide: true),
                        ],
                    ],
                    [
                        'label' => __('Organization'),
                        'eyebrow' => __('Step 2 of 4'),
                        'heading' => __('Who owns this request?'),
                        'fields' => $this->contactFields(withPhone: false),
                    ],
                    [
                        'label' => __('Project'),
                        'eyebrow' => __('Step 3 of 4'),
                        'heading' => __('What timing, budget and evidence should we review?'),
                        'fields' => [
                            $this->field('timeframe', __('Timeframe'), help: __('Include the proposal deadline and expected delivery window when known.'), maxlength: 100),
                            $this->field('budget_range', __('Indicative budget'), help: __('A range is optional and helps us shape a proportionate response.'), maxlength: 100),
                            [
                                ...$this->field('attachments', __('Supporting files'), 'file', help: __('Up to 5 PDF, DOCX, XLSX or PPTX files; 20 MB per file. Files remain private and are security scanned before staff can access them.'), wide: true),
                                'accept' => '.pdf,.docx,.xlsx,.pptx',
                                'multiple' => true,
                            ],
                        ],
                        'note' => __('Selecting a file does not mean it has been approved. After submission, each file remains unavailable until security processing is complete.'),
                    ],
                    [
                        'label' => __('Review'),
                        'eyebrow' => __('Step 4 of 4'),
                        'heading' => __('Review and submit the brief'),
                        'review' => $this->review(__('Assignment description')),
                        'consent' => __('I understand that these details and files will be used to assess and respond to this request under the current privacy notice.'),
                        'note' => __('After submission, the confirmation screen provides the authoritative reference. Keep that reference for follow-up.'),
                    ],
                ],
            ],
            'context' => [
                'eyebrow' => __('Secure proposal intake'),
                'title' => __('A useful brief makes the evaluation criteria visible.'),
                'text' => __('Include the assignment context, desired outcomes, constraints and proposal deadline. Avoid passwords and unnecessary personal data.'),
                'items' => [__('Assignment and outcomes'), __('Decision ownership'), __('Timing and budget'), __('Relevant supporting files')],
                'footerTitle' => __('File handling'),
                'footerText' => __('Files remain private and unavailable to staff until the configured security processing is complete.'),
                'links' => [],
            ],
        ]);
    }

    public function contact(): Response
    {
        $features = app(PublicUiSettings::class)->viewData()['features'];

        return $this->engagement('contact', PublicPage::seo(
            'contact',
            __('Contact us'),
            __('Contact Impact Consulting for general, partnership or media inquiries, or choose the dedicated consultation and proposal routes.'),
            'ContactPage',
        ), [
            'breadcrumbs' => PublicPage::breadcrumbs([__('Contact') => null]),
            'header' => [
                'eyebrow' => __('Contact'),
                'title' => __('Choose the right route for your request.'),
                'summary' => __('Use this form for general, partnership or media inquiries. Consultation and proposal requests have dedicated secure pathways.'),
            ],
            'form' => [
                'id' => 'contact',
                'action' => route('contact.store'),
                'multipart' => false,
                'hidden' => ['policy_version' => $this->policyVersion()],
                'intro' => null,
                'progressLabel' => null,
                'submitLabel' => __('Submit request'),
                'steps' => [
                    [
                        'label' => __('Contact'),
                        'eyebrow' => null,
                        'heading' => null,
                        'fields' => [
                            [
                                ...$this->field('type', __('Request type'), 'select', required: true, wide: true),
                                'options' => [
                                    ['value' => 'contact', 'label' => __('General contact')],
                                    ['value' => 'partnership', 'label' => __('Partnership inquiry')],
                                    ['value' => 'media', 'label' => __('Media inquiry')],
                                ],
                            ],
                            $this->field('contact_name', __('Full name'), required: true, maxlength: 160, autocomplete: 'name'),
                            $this->field('email', __('Work email'), 'email', required: true, maxlength: 255, autocomplete: 'email'),
                            $this->field('organization_name', __('Organization'), maxlength: 200, autocomplete: 'organization', wide: true),
                            $this->field('description', __('Message'), 'textarea', required: true, help: __('Do not include sensitive personal or confidential information at this stage.'), minlength: 20, maxlength: 10000, wide: true),
                        ],
                        'consent' => __('I understand that Impact Consulting will use these details to assess and respond to this request under the current privacy notice.'),
                    ],
                ],
            ],
            'context' => [
                'eyebrow' => __('Contact'),
                'title' => __('Choose the right route for your request.'),
                'text' => __('Use this form for general, partnership or media inquiries. Consultation and proposal requests have dedicated secure pathways.'),
                'items' => [],
                'footerTitle' => null,
                'footerText' => null,
                'links' => array_values(array_filter([
                    $features['consultation'] ? ['label' => __('Request a consultation'), 'href' => route('consultation.create'), 'primary' => true] : null,
                    $features['rfp'] ? ['label' => __('Submit an RFP'), 'href' => route('rfp.create'), 'primary' => false] : null,
                ])),
            ],
        ]);
    }

    /** @param  array<string, mixed>  $props */
    private function engagement(string $pageKey, SeoMetadataBuilder $seo, array $props): Response
    {
        return PublicPage::render('Public/Engagement', $pageKey, [
            ...$props,
            'copy' => [
                'required' => __('required'),
                'continue' => __('Continue'),
                'previous' => __('Previous step'),
                'errorSummary' => __('Please correct the following fields before continuing.'),
                'notProvided' => __('Not provided'),
                'chooseFiles' => __('Choose files'),
            ],
        ], seo: $seo);
    }

    /** @return array<int, array<string, mixed>> */
    private function contactFields(bool $withPhone): array
    {
        return array_values(array_filter([
            $this->field('contact_name', __('Full name'), required: true, maxlength: 160, autocomplete: 'name'),
            $this->field('email', __('Work email'), 'email', required: true, maxlength: 255, autocomplete: 'email'),
            $this->field('organization_name', __('Organization'), maxlength: 200, autocomplete: 'organization'),
            $this->field('role', __('Role'), maxlength: 160, autocomplete: 'organization-title'),
            $withPhone ? $this->field('phone', __('Phone'), 'tel', maxlength: 40, autocomplete: 'tel', wide: true) : null,
        ]));
    }

    /** @return array<int, array{label: string, field: string}> */
    private function review(string $descriptionLabel): array
    {
        return [
            ['label' => $descriptionLabel, 'field' => 'description'],
            ['label' => __('Full name'), 'field' => 'contact_name'],
            ['label' => __('Work email'), 'field' => 'email'],
            ['label' => __('Organization'), 'field' => 'organization_name'],
            ['label' => __('Timeframe'), 'field' => 'timeframe'],
            ['label' => __('Indicative budget'), 'field' => 'budget_range'],
        ];
    }

    /** @return array<string, mixed> */
    private function field(
        string $name,
        string $label,
        string $type = 'text',
        bool $required = false,
        ?string $help = null,
        ?int $minlength = null,
        ?int $maxlength = null,
        ?string $autocomplete = null,
        bool $wide = false,
    ): array {
        return compact('name', 'label', 'type', 'required', 'help', 'minlength', 'maxlength', 'autocomplete', 'wide');
    }

    private function policyVersion(): string
    {
        return (string) app(PublicUiSettings::class)->viewData()['privacy']['policy_version'];
    }
}
