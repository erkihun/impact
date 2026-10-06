<?php

declare(strict_types=1);

use App\Data\PageComposition\CompositionViewData;
use App\Data\PageComposition\SectionViewData;
use App\Enums\PageSectionType;
use App\Enums\PageTemplateType;
use App\Models\Setting;
use App\Services\PageComposer;
use App\Services\Seo\StructuredDataValidator;
use App\Support\Settings\EffectiveSettings;

it('describes the organization and website from configured settings on every page', function (): void {
    Setting::query()->create(['key' => 'seo.social_linkedin_url', 'scope' => 'global', 'type' => 'url', 'value' => 'https://www.linkedin.com/company/impact']);
    EffectiveSettings::flushCaches();

    $nodes = structuredData($this->get('/')->assertOk());

    expect($nodes['Organization'])->toMatchArray([
        '@id' => 'http://localhost/#organization',
        'name' => 'Impact Consulting Organization',
        'url' => 'http://localhost/',
        'sameAs' => ['https://www.linkedin.com/company/impact'],
    ])
        ->and($nodes['Organization']['address']['addressCountry'])->toBe('ET')
        ->and($nodes['WebSite']['publisher'])->toBe(['@id' => 'http://localhost/#organization'])
        ->and($nodes['WebPage']['inLanguage'])->toBe('en')
        ->and($nodes['Organization'])->not->toHaveKeys(['aggregateRating', 'review', 'award']);
});

it('emits typed, valid JSON-LD for each content type', function (string $path, string $type, array $expected): void {
    publishService('digital-transformation');
    publishExpert('jane-doe');
    publishInsight('strategy-reality');
    publishCaseStudy('national-delivery');
    publishEvent('leadership-systems');
    publishVacancy('senior-consultant');

    $response = $this->get($path)->assertOk();
    $nodes = structuredData($response);

    expect($nodes)->toHaveKeys([$type, 'BreadcrumbList'])
        ->and($nodes[$type])->toMatchArray($expected)
        ->and($nodes['BreadcrumbList']['itemListElement'][0])->toMatchArray(['position' => 1, 'name' => 'Home', 'item' => 'http://localhost/']);

    preg_match('#<script type="application/ld\+json" data-inertia="structured-data">(.*?)</script>#s', $response->getContent(), $match);
    expect(app(StructuredDataValidator::class)->validateScripts([$match[1]], $path)->hasBlocking())->toBeFalse();
})->with([
    'Service' => ['/services/digital-transformation', 'Service', ['name' => 'Digital transformation', 'provider' => ['@id' => 'http://localhost/#organization']]],
    'Person' => ['/experts/jane-doe', 'Person', ['name' => 'Jane Doe', 'jobTitle' => 'Strategy Lead']],
    'Article' => ['/insights/strategy-reality', 'Article', ['headline' => 'Strategy that survives contact with reality', 'author' => ['@id' => 'http://localhost/#organization']]],
    'Case study article' => ['/case-studies/national-delivery', 'Article', ['articleSection' => 'Case study']],
    'Event' => ['/events/leadership-systems', 'Event', ['name' => 'From strategy to delivery', 'eventAttendanceMode' => 'https://schema.org/MixedEventAttendanceMode']],
    'JobPosting' => ['/careers/senior-consultant', 'JobPosting', ['title' => 'Senior Consultant', 'employmentType' => 'FULL_TIME']],
]);

it('marks expert pages as profile pages and keeps the private meeting link out of event markup', function (): void {
    publishExpert('jane-doe');
    $event = publishEvent('online-briefing');
    $event->forceFill(['meeting_url_encrypted' => 'https://meet.example.com/secret-room'])->save();

    expect(structuredData($this->get('/experts/jane-doe'))['ProfilePage']['mainEntity'])->toBe(['@id' => 'http://localhost/experts/jane-doe#person']);
    expect($this->get('/events/online-briefing')->getContent())->not->toContain('secret-room');
});

it('uses NewsArticle and Report only for insights of that kind', function (): void {
    publishInsight('market-update', 'Market update', 'news');
    publishInsight('annual-review', 'Annual review', 'report');

    expect(structuredData($this->get('/insights/market-update')))->toHaveKey('NewsArticle')
        ->and(structuredData($this->get('/insights/annual-review')))->toHaveKey('Report');
});

it('removes JobPosting markup once applications close', function (): void {
    $vacancy = publishVacancy('closing-role');
    $vacancy->update(['closes_at' => now('UTC')->subDay()]);

    expect(structuredData($this->get('/careers/closing-role')))->not->toHaveKey('JobPosting');
});

it('rejects fabricated ratings, awards and retired-language URLs', function (): void {
    $result = app(StructuredDataValidator::class)->validateNodes([
        ['@type' => 'Organization', 'name' => 'X', 'url' => 'https://x.test/', 'aggregateRating' => ['ratingValue' => 5]],
        ['@type' => 'WebPage', 'name' => 'Y', 'url' => 'https://x.test/am/services'],
    ], '/');

    expect(collect($result->issues())->pluck('code')->all())->toContain('structured-data-forbidden-property', 'structured-data-legacy-locale-url');
});

it('adds FAQPage only for question and answer content that is visibly published on the page', function (): void {
    app()->instance(PageComposer::class, new class
    {
        public function published(string $pageKey, string $locale): ?CompositionViewData
        {
            return $pageKey !== 'about' ? null : new CompositionViewData(
                'composition', 'about', 'en', PageTemplateType::Institutional, 1, [
                    new SectionViewData('header', 'header', PageSectionType::PageHeader, 'default', 'public.sections.page-header', ['heading' => 'About us'], [], [], [], []),
                    new SectionViewData('faq', 'faq', PageSectionType::Faq, 'default', 'public.sections.faq', [
                        'heading' => 'Questions',
                        'items' => [['question' => 'How does an engagement start?', 'answer' => 'With a short scoping conversation about the outcome you need.']],
                    ], [], [], [], []),
                ],
            );
        }
    });

    $faq = structuredData($this->get('/about')->assertOk())['FAQPage'];
    expect($faq['mainEntity'][0])->toMatchArray([
        '@type' => 'Question',
        'name' => 'How does an engagement start?',
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'With a short scoping conversation about the outcome you need.'],
    ]);
    expect(structuredData($this->get('/contact')))->not->toHaveKey('FAQPage');
});
