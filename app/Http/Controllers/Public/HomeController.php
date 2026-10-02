<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CaseStudyVersion;
use App\Models\ExpertVersion;
use App\Models\IndustryVersion;
use App\Models\InsightVersion;
use App\Models\ServiceVersion;
use App\Support\Settings\HomepageHeroSettings;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    private const FEATURE_IMAGE = 'images/impact-hero-clean.svg';

    public function __invoke(HomepageHeroSettings $heroSettings): Response
    {
        $locale = app()->getLocale();
        $visibleServices = ServiceVersion::query()
            ->where('locale', $locale)->publiclyVisible();
        $visibleIndustries = IndustryVersion::query()
            ->where('locale', $locale)->publiclyVisible();
        $visibleCaseStudies = CaseStudyVersion::query()
            ->where('locale', $locale)->publiclyVisible();
        $visibleExperts = ExpertVersion::query()
            ->where('locale', $locale)->publiclyVisible();
        $visibleInsights = InsightVersion::query()
            ->where('locale', $locale)->publiclyVisible();

        $caseStudy = (clone $visibleCaseStudies)->latest('version_no')->first();
        $experts = (clone $visibleExperts)->with('expert.profileMedia')->orderBy('display_name')->latest('version_no')->get()
            ->unique('expert_id')
            ->values();
        $insight = (clone $visibleInsights)->with('insight')->latest('version_no')->first();

        return Inertia::render('Public/Home', [
            'meta' => [
                'title' => __('Impact Consulting — Ideas into measurable change'),
                'description' => null,
            ],
            'heroSlider' => [
                ...$heroSettings->viewData($locale),
                'label' => __('Homepage hero'),
            ],
            'ledger' => collect([
                [
                    'value' => (clone $visibleServices)->distinct()->count('service_id'),
                    'label' => __('Published services'),
                    'context' => __('Current advisory capabilities'),
                    'href' => route('services.index', ['locale' => $locale]),
                ],
                [
                    'value' => (clone $visibleIndustries)->distinct()->count('industry_id'),
                    'label' => __('Industry contexts'),
                    'context' => __('Current published sector coverage'),
                    'href' => route('industries.index', ['locale' => $locale]),
                ],
                [
                    'value' => (clone $visibleExperts)->distinct()->count('expert_id'),
                    'label' => __('Published experts'),
                    'context' => __('Named public profiles'),
                    'href' => route('experts.index', ['locale' => $locale]),
                ],
                [
                    'value' => (clone $visibleInsights)->distinct()->count('insight_id'),
                    'label' => __('Knowledge products'),
                    'context' => __('Current published insights'),
                    'href' => route('insights.index', ['locale' => $locale]),
                ],
            ])->filter(fn (array $item): bool => $item['value'] > 0)->values(),
            'services' => (clone $visibleServices)->latest('version_no')->limit(6)->get()
                ->map(fn (ServiceVersion $service): array => [
                    'name' => $service->name,
                    'summary' => Str::limit((string) ($service->summary ?: $service->problem_statement), 130),
                    'href' => route('services.show', ['locale' => $locale, 'slug' => $service->slug]),
                ]),
            'caseStudy' => $caseStudy ? [
                'title' => $caseStudy->title,
                'outcomes' => $caseStudy->outcomes ? Str::limit((string) $caseStudy->outcomes, 200) : null,
                'href' => route('case-studies.show', ['locale' => $locale, 'slug' => $caseStudy->slug]),
                'image' => asset(self::FEATURE_IMAGE),
            ] : null,
            'testimonials' => [
                [
                    'quote' => __('The team helped us turn a complex reform agenda into decisions, routines and evidence we could use every week.'),
                    'name' => __('Programme Director'),
                    'role' => __('Public institution transformation programme'),
                    'metric' => __('12 workstreams aligned'),
                ],
                [
                    'quote' => __('Their facilitation made the trade-offs visible and gave our leadership group a shared operating rhythm.'),
                    'name' => __('Executive Sponsor'),
                    'role' => __('Institutional strengthening engagement'),
                    'metric' => __('6 agencies coordinated'),
                ],
                [
                    'quote' => __('The monitoring approach was practical. It helped teams learn faster without adding unnecessary reporting burden.'),
                    'name' => __('Learning Lead'),
                    'role' => __('Research and delivery support'),
                    'metric' => __('Quarterly learning cycle'),
                ],
            ],
            'industries' => (clone $visibleIndustries)->latest('version_no')->limit(4)->get()
                ->map(fn (IndustryVersion $industry): array => [
                    'name' => $industry->name,
                    'summary' => $industry->summary ? Str::limit((string) $industry->summary, 120) : null,
                    'href' => route('industries.show', ['locale' => $locale, 'slug' => $industry->slug]),
                ]),
            'experts' => $experts->map(function (ExpertVersion $expert) use ($locale): array {
                $photo = $expert->expert?->profileMedia;

                return [
                    'name' => $expert->display_name,
                    'title' => $expert->professional_title,
                    'initial' => Str::upper(Str::substr((string) $expert->display_name, 0, 1)),
                    'photo' => $photo?->isPubliclyUsable() ? $photo->publicUrl() : null,
                    'photoAlt' => $photo?->alt_text ?: $expert->display_name,
                    'href' => route('experts.show', ['locale' => $locale, 'slug' => $expert->slug]),
                ];
            }),
            'insight' => $insight ? [
                'title' => $insight->title,
                'excerpt' => $insight->excerpt ? Str::limit((string) $insight->excerpt, 150) : null,
                'href' => route('insights.show', ['locale' => $locale, 'slug' => $insight->slug]),
            ] : null,
            'links' => [
                'services' => route('services.index', ['locale' => $locale]),
                'caseStudies' => route('case-studies.index', ['locale' => $locale]),
                'consultation' => route('consultation.create', ['locale' => $locale]),
                'contact' => route('contact.create', ['locale' => $locale]),
            ],
            'copy' => [
                'ledgerTitle' => __('Current publication record'),
                'servicesEyebrow' => __('Advisory architecture'),
                'servicesTitle' => __('Where complex problems become workable choices'),
                'servicesLead' => __('Each capability begins with the decision or delivery problem—not a pre-packaged solution.'),
                'servicesLink' => __('Explore the capability system'),
                'caseEyebrow' => __('Transformation record'),
                'caseLink' => __('Examine the full record'),
                'caseStudies' => __('Case studies'),
                'testimonialsEyebrow' => __('Client testimony'),
                'testimonialsTitle' => __('What partners say after the work becomes real'),
                'industriesEyebrow' => __('Sector intelligence'),
                'industriesTitle' => __('Context changes the answer'),
                'industriesLead' => __('Our sector work starts with institutions, incentives and operating realities. Explore the published contexts in which we advise.'),
                'perspectivesEyebrow' => __('Expert perspective'),
                'perspectivesTitle' => __('People and published thinking'),
                'meetTeam' => __('Meet the advisory team'),
                'insight' => __('Insight'),
                'readInsight' => __('Read the insight'),
                'learnMore' => __('Learn more'),
                'engagementEyebrow' => __('Engagement pathway'),
                'engagementTitle' => __('Bring the question. We will help structure the next move.'),
                'engagementLead' => __('Share the outcome, context, stakeholders and timing. Our team will review the request and identify the most relevant response.'),
                'steps' => [
                    __('Submit the essential context'),
                    __('Receive a structured review'),
                    __('Agree the right engagement route'),
                ],
                'requestConsultation' => __('Request a consultation'),
                'otherRoute' => __('Choose another route'),
            ],
        ]);
    }
}
