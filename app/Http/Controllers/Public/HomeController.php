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
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __invoke(HomepageHeroSettings $heroSettings): View
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

        return view('public.home', [
            'heroSlider' => $heroSettings->viewData($locale),
            'services' => (clone $visibleServices)->latest('version_no')->limit(6)->get(),
            'industries' => (clone $visibleIndustries)->latest('version_no')->limit(4)->get(),
            'caseStudies' => (clone $visibleCaseStudies)->latest('version_no')->limit(3)->get(),
            'experts' => (clone $visibleExperts)
                ->with('expert.profileMedia')
                ->latest('version_no')->limit(3)->get(),
            'insights' => (clone $visibleInsights)
                ->with('insight')
                ->latest('version_no')->limit(3)->get(),
            'impactIndex' => [
                [
                    'value' => (clone $visibleServices)->distinct()->count('service_id'),
                    'label' => __('Published services'),
                    'context' => __('Current advisory capabilities'),
                    'href' => route('services.index', ['locale' => $locale]),
                    'icon' => 'services',
                ],
                [
                    'value' => (clone $visibleIndustries)->distinct()->count('industry_id'),
                    'label' => __('Industry contexts'),
                    'context' => __('Current published sector coverage'),
                    'href' => route('industries.index', ['locale' => $locale]),
                    'icon' => 'industries',
                ],
                [
                    'value' => (clone $visibleExperts)->distinct()->count('expert_id'),
                    'label' => __('Published experts'),
                    'context' => __('Named public profiles'),
                    'href' => route('experts.index', ['locale' => $locale]),
                    'icon' => 'experts',
                ],
                [
                    'value' => (clone $visibleInsights)->distinct()->count('insight_id'),
                    'label' => __('Knowledge products'),
                    'context' => __('Current published insights'),
                    'href' => route('insights.index', ['locale' => $locale]),
                    'icon' => 'insights',
                ],
            ],
        ]);
    }
}
