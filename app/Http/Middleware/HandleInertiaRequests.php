<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PublicNavigation;
use App\Support\Settings\PublicUiSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template loaded on the first visit to an Inertia page.
     *
     * @var string
     */
    protected $rootView = 'inertia';

    // Only public content needs the rendering service. Authentication secrets
    // and private administration data stay in the PHP/browser session.
    protected $withoutSsr = ['admin*', 'login', 'forgot-password', 'reset-password*', 'invitations/*', 'mfa*', 'verify-email', 'confirm-password', 'profile*', 'dashboard'];

    /**
     * Props shared with every React page: site identity and settings, the
     * localized navigation shell, flash messages and the shell's UI strings.
     * Copy is translated here so the client never downloads the dictionary.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'site' => fn (): array => $this->site($request),
            'navigation' => fn (): array => $this->navigation(),
            'flash' => fn (): array => [
                'status' => $request->session()->get('status'),
                'submission' => $request->session()->get('submission'),
            ],
            'ui' => fn (): array => $this->uiStrings(),
            'workspace' => fn (): ?array => $request->is('admin', 'admin/*', 'login', 'forgot-password', 'reset-password*', 'reset-password/*', 'invitations/*', 'mfa', 'mfa/*', 'verify-email', 'confirm-password', 'profile', 'dashboard')
                ? \App\Support\Inertia\WorkspaceShell::data($request) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function site(Request $request): array
    {
        $experience = app(PublicUiSettings::class)->viewData();
        $current = $experience['localization']['current'];
        $alternate = $experience['localization']['alternate'];
        $alternateUrl = $alternate
            ? (preg_replace(
                '#/'.preg_quote($current, '#').'(?=/|$)#',
                '/'.$alternate,
                $request->url(),
                1,
            ) ?: route('localized-home', ['locale' => $alternate]))
            : null;

        return [
            'identity' => $experience['identity'],
            'seo' => [
                'defaultTitle' => $experience['seo']['default_title'],
                'titleSuffix' => $experience['seo']['title_suffix'],
                'defaultDescription' => $experience['seo']['default_description'],
                'robots' => $experience['seo']['robots'],
                'canonicalUrl' => $request->url(),
                'defaultLocaleUrl' => route('localized-home', ['locale' => $experience['localization']['default']]),
            ],
            'features' => $experience['features'],
            'privacy' => $experience['privacy'],
            'maintenance' => $experience['maintenance'],
            'locale' => [
                'current' => $current,
                'alternate' => $alternate,
                'alternateUrl' => $alternateUrl,
                'alternateLabel' => $alternate === 'am' ? 'አማርኛ' : 'English',
            ],
            'csrfToken' => csrf_token(),
            'routes' => [
                'home' => route('localized-home', ['locale' => $current]),
                'search' => route('search', ['locale' => $current]),
                'consultation' => route('consultation.create', ['locale' => $current]),
                'rfp' => route('rfp.create', ['locale' => $current]),
                'contact' => route('contact.create', ['locale' => $current]),
                'newsletter' => route('newsletter.subscribe', ['locale' => $current]),
                'consent' => route('consent.update'),
                'privacy' => route('legal.privacy', ['locale' => $current]),
                'status' => route('system-status'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function navigation(): array
    {
        $locale = app()->getLocale();
        $navigation = app(PublicNavigation::class);
        $labels = $navigation->location('primary', $locale)->pluck('label', 'route');
        $url = static fn (string $name): string => route($name, ['locale' => $locale]);
        $link = static fn (string $route, string $title, string $description, string $icon): array => [
            'href' => route($route, ['locale' => $locale]),
            'title' => $title,
            'description' => $description,
            'icon' => $icon,
        ];
        $currentRoute = (string) request()->route()?->getName();
        $active = static fn (string ...$patterns): bool => collect($patterns)
            ->contains(static fn (string $pattern): bool => str($currentRoute)->is($pattern));

        return [
            'primary' => [
                ['type' => 'link', 'id' => 'home', 'label' => $labels['localized-home'] ?? __('Home'), 'href' => $url('localized-home'), 'active' => $active('home', 'localized-home')],
                ['type' => 'menu', 'id' => 'about', 'label' => $labels['about.show'] ?? __('About'), 'active' => $active('about.*'),
                    'eyebrow' => __('About Impact Consulting'),
                    'intro' => __('Understand our purpose, working approach and institutional commitments.'),
                    'links' => [
                        $link('about.show', __('Who we are'), __('Purpose, values and the way we work'), 'about'),
                        $link('experts.index', __('Our experts'), __('Experience, credentials and selected work'), 'experts'),
                        $link('careers.index', __('Careers'), __('Current opportunities and application routes'), 'careers'),
                        $link('contact.create', __('Contact'), __('Find the right route for your request'), 'contact'),
                    ],
                    'promo' => ['title' => __('Have a specific assignment?'), 'text' => __('Share a structured brief through our secure proposal route.'), 'label' => __('Submit an RFP'), 'href' => $url('rfp.create')],
                ],
                ['type' => 'menu', 'id' => 'services', 'label' => $labels['services.index'] ?? __('Services'), 'active' => $active('services.*'),
                    'eyebrow' => __('Consulting services'),
                    'intro' => __('Focused advice that connects diagnosis, strategy, delivery and learning.'),
                    'links' => [
                        $link('services.index', __('Explore all services'), __('Browse current published capabilities'), 'services'),
                        $link('case-studies.index', __('Evidence and outcomes'), __('See related work and measurable results'), 'case-studies'),
                    ],
                    'promo' => ['title' => __('Not sure which service fits?'), 'text' => __('Describe the outcome you need and we will route your request.'), 'label' => __('Request a consultation'), 'href' => $url('consultation.create')],
                ],
                ['type' => 'menu', 'id' => 'industries', 'label' => $labels['industries.index'] ?? __('Industries'), 'active' => $active('industries.*'),
                    'eyebrow' => __('Sector insight'),
                    'intro' => __('Explore sector context, relevant services and approved evidence.'),
                    'links' => [
                        $link('industries.index', __('Browse industries'), __('Find advice grounded in your operating context'), 'industries'),
                        $link('case-studies.index', __('Related case studies'), __('Review authorized examples and outcomes'), 'case-studies'),
                    ],
                    'promo' => null,
                ],
                ['type' => 'link', 'id' => 'experts', 'label' => $labels['experts.index'] ?? __('Experts'), 'href' => $url('experts.index'), 'active' => $active('experts.*')],
                ['type' => 'link', 'id' => 'case-studies', 'label' => $labels['case-studies.index'] ?? __('Case studies'), 'href' => $url('case-studies.index'), 'active' => $active('case-studies.*')],
                ['type' => 'menu', 'id' => 'insights', 'label' => $labels['insights.index'] ?? __('Insights'), 'active' => $active('insights.*', 'events.*'),
                    'eyebrow' => __('Knowledge centre'),
                    'intro' => __('Evidence, analysis and practical learning from our current published work.'),
                    'links' => [
                        $link('insights.index', __('Articles and reports'), __('Browse published knowledge content'), 'insights'),
                        $link('events.index', __('Events'), __('Upcoming learning and registration opportunities'), 'events'),
                    ],
                    'promo' => null,
                ],
            ],
            'mobile' => [
                ['label' => $labels['localized-home'] ?? __('Home'), 'href' => $url('localized-home')],
                ['label' => $labels['about.show'] ?? __('About'), 'href' => $url('about.show')],
                ['label' => $labels['services.index'] ?? __('Services'), 'href' => $url('services.index')],
                ['label' => $labels['industries.index'] ?? __('Industries'), 'href' => $url('industries.index')],
                ['label' => $labels['experts.index'] ?? __('Experts'), 'href' => $url('experts.index')],
                ['label' => $labels['case-studies.index'] ?? __('Case studies'), 'href' => $url('case-studies.index')],
                ['label' => $labels['insights.index'] ?? __('Insights'), 'href' => $url('insights.index')],
                ['label' => __('Events'), 'href' => $url('events.index')],
                ['label' => __('Careers'), 'href' => $url('careers.index')],
                ['label' => __('Contact'), 'href' => $url('contact.create')],
            ],
            'footer' => collect(['footer_explore', 'footer_engage', 'footer_legal'])
                ->mapWithKeys(static fn (string $location): array => [
                    $location => $navigation->location($location, $locale)
                        ->map(static fn (array $item): array => ['label' => $item['label'], 'href' => $item['url']])
                        ->values()
                        ->all(),
                ])
                ->all(),
        ];
    }

    /**
     * Shell copy for the React layout, translated server-side.
     *
     * @return array<string, string>
     */
    private function uiStrings(): array
    {
        return [
            'skip' => __('Skip to content'),
            'breadcrumb' => __('Breadcrumb'),
            'nothingPublished' => __('Nothing published yet.'),
            'primaryNavigation' => __('Primary navigation'),
            'mobileNavigation' => __('Mobile navigation'),
            'navigation' => __('Navigation'),
            'openMenu' => __('Open menu'),
            'closeMenu' => __('Close menu'),
            'search' => __('Search'),
            'consultation' => __('Consultation'),
            'requestConsultation' => __('Request a consultation'),
            'homeLabel' => __(':name home', ['name' => app(PublicUiSettings::class)->viewData()['identity']['name']]),
            'consulting' => __('Consulting'),
            'reference' => __('Reference'),
            'viewStatus' => __('View system status'),
            'explore' => __('Explore'),
            'engage' => __('Engage'),
            'legal' => __('Legal and privacy'),
            'privacyChoices' => __('Privacy choices'),
            'newsletterTitle' => __('Practical insight, periodically'),
            'email' => __('Email address'),
            'join' => __('Join'),
            'newsletterConsent' => __('I agree to receive the newsletter. Double opt-in applies and I can unsubscribe at any time.'),
            'consentTitle' => __('Your choices about cookies and storage'),
            'consentSummary' => __('We use necessary storage to keep this site secure and working. Optional storage for preferences, analytics and marketing is used only if you agree. You can change your choice at any time.'),
            'consentPrivacy' => __('Read the privacy notice'),
            'consentFailed' => __('Your choice was not saved. Check your connection and try again.'),
            'manageChoices' => __('Manage choices'),
            'rejectOptional' => __('Reject optional'),
            'acceptOptional' => __('Accept optional'),
            'saveChoices' => __('Save my choices'),
            'saving' => __('Saving…'),
            'manageTitle' => __('Manage your privacy choices'),
            'manageIntro' => __('Choose which optional storage you allow. Necessary storage cannot be switched off because the site will not work without it.'),
            'alwaysActive' => __('Always active'),
            'necessary' => __('Necessary'),
            'necessaryDescription' => __('Keeps your session secure, protects forms against misuse and remembers your privacy choice.'),
            'preferences' => __('Preferences'),
            'preferencesDescription' => __('Remembers choices such as your language and display settings so you do not have to set them again.'),
            'analytics' => __('Analytics'),
            'analyticsDescription' => __('Helps us understand which services and insights are useful. We do not record form text or uploaded documents.'),
            'marketing' => __('Marketing'),
            'marketingDescription' => __('Measures whether our campaigns and newsletters reach the right audiences.'),
            'allowed' => __('Allowed'),
            'notAllowed' => __('Not allowed'),
            'close' => __('Close privacy choices'),
            'previousSlide' => __('Previous slide'),
            'nextSlide' => __('Next slide'),
            'pauseSlides' => __('Pause slides'),
            'playSlides' => __('Play slides'),
            'chooseSlide' => __('Choose a slide'),
            'slideOf' => __('Slide :current of :total'),
            'slide' => __('slide'),
        ];
    }
}
