<?php

declare(strict_types=1);
use App\Models\CaseStudy;
use App\Models\CaseStudyVersion;
use App\Models\Event;
use App\Models\Expert;
use App\Models\ExpertVersion;
use App\Models\Industry;
use App\Models\IndustryVersion;
use App\Models\Insight;
use App\Models\InsightVersion;
use App\Models\MediaAsset;
use App\Models\Service;
use App\Models\ServiceVersion;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function privilegedSession(User $user): array
{
    $now = now('UTC')->timestamp;

    return [
        'authenticated_at' => $now,
        'last_session_activity_at' => $now,
        'session_version' => $user->session_version,
        'mfa_verified_at' => $now,
    ];
}

/*
|--------------------------------------------------------------------------
| SEO fixtures
|--------------------------------------------------------------------------
*/

/**
 * Indexing is only ever allowed in production; SEO tests that assert the
 * production directives switch the environment and use HTTPS requests.
 */
function asProduction(): void
{
    app()['env'] = 'production';
    app()->forgetScopedInstances();
}

function seoAuthor(): User
{
    return User::query()->first() ?? User::factory()->create();
}

/** @param  array<string, mixed>  $version */
function publishService(string $slug, string $name = 'Digital transformation', array $version = [], string $status = 'published'): ServiceVersion
{
    $service = Service::query()->create([
        'code' => strtoupper(Str::random(10)),
        'status' => $status,
        'created_by' => seoAuthor()->id,
    ]);

    return ServiceVersion::query()->create([
        'service_id' => $service->id,
        'locale' => 'en',
        'version_no' => 1,
        'slug' => $slug,
        'name' => $name,
        'summary' => "Practical {$name} advice that connects strategy, operating models and delivery for public institutions.",
        'problem_statement' => 'Institutions need clearer decisions and delivery.',
        'approach' => 'Diagnosis, co-design and disciplined implementation.',
        'deliverables' => ['Diagnostic', 'Roadmap'],
        'workflow_state' => 'published',
        ...$version,
    ]);
}

function publishIndustry(string $slug, string $name = 'Public sector'): IndustryVersion
{
    $industry = Industry::query()->create(['code' => strtoupper(Str::random(10)), 'status' => 'published']);

    return IndustryVersion::query()->create([
        'industry_id' => $industry->id, 'locale' => 'en', 'version_no' => 1, 'slug' => $slug, 'name' => $name,
        'summary' => "Advice for {$name} organizations on reform priorities, delivery systems and institutional performance.",
        'workflow_state' => 'published',
    ]);
}

function publishExpert(string $slug, string $name = 'Jane Doe', ?MediaAsset $photo = null): ExpertVersion
{
    $expert = Expert::query()->create([
        'status' => 'published', 'public_email_enabled' => false, 'publication_authorized_at' => now(),
        'profile_media_id' => $photo?->id,
    ]);

    return ExpertVersion::query()->create([
        'expert_id' => $expert->id, 'locale' => 'en', 'version_no' => 1, 'slug' => $slug,
        'display_name' => $name, 'professional_title' => 'Strategy Lead',
        'biography' => "{$name} helps leadership teams translate public value goals into operating models and measurable outcomes.",
        'qualifications' => ['MSc Public Policy'], 'languages' => ['English'], 'workflow_state' => 'published',
    ]);
}

function publishInsight(string $slug, string $title = 'Strategy that survives contact with reality', string $type = 'article'): InsightVersion
{
    $insight = Insight::query()->create(['type' => $type, 'status' => 'published', 'published_at' => now()->subDay()]);

    return InsightVersion::query()->create([
        'insight_id' => $insight->id, 'locale' => 'en', 'version_no' => 1, 'slug' => $slug, 'title' => $title,
        'excerpt' => 'Four choices that turn a strategy document into a practical operating discipline for leadership teams.',
        'body' => 'The strongest strategies make trade-offs explicit.', 'workflow_state' => 'published',
    ]);
}

function publishCaseStudy(string $slug, string $title = 'Building a delivery system for a national programme'): CaseStudyVersion
{
    $caseStudy = CaseStudy::query()->create(['status' => 'published', 'client_display_mode' => 'anonymized', 'featured' => false]);

    return CaseStudyVersion::query()->create([
        'case_study_id' => $caseStudy->id, 'locale' => 'en', 'version_no' => 1, 'slug' => $slug, 'title' => $title,
        'challenge' => 'A multi-agency programme needed clearer ownership and faster decisions.',
        'approach' => 'A practical delivery rhythm and decision-ready reporting.',
        'outcomes' => 'Leadership gained a shared view of priorities, risks and progress across all agencies.',
        'workflow_state' => 'published',
    ]);
}

function publishEvent(string $slug, string $status = 'registration_open'): Event
{
    return Event::query()->create([
        'status' => $status, 'format' => 'hybrid', 'title' => 'From strategy to delivery', 'slug' => $slug, 'locale' => 'en',
        'description' => 'A practical session on turning leadership priorities into visible delivery routines.',
        'starts_at' => now('UTC')->addMonth(), 'ends_at' => now('UTC')->addMonth()->addHours(2),
        'timezone' => 'Africa/Addis_Ababa', 'venue' => 'Addis Ababa', 'capacity' => 50,
        'registration_closes_at' => now('UTC')->addMonth()->subDay(),
    ]);
}

function publishVacancy(string $slug, string $status = 'published'): Vacancy
{
    return Vacancy::query()->create([
        'reference_no' => 'ICO-'.strtoupper(Str::random(6)), 'status' => $status, 'title' => 'Senior Consultant',
        'slug' => $slug, 'locale' => 'en', 'type' => 'full_time', 'location' => 'Addis Ababa',
        'description' => 'Lead evidence-driven strategy engagements.', 'requirements' => 'Postgraduate qualification.',
        'opens_at' => now('UTC')->subDay(), 'closes_at' => now('UTC')->addMonth(), 'application_limit' => 100,
    ]);
}

/** @return array<string, mixed> JSON-LD @graph nodes of a response, keyed by @type */
function structuredData(TestResponse $response): array
{
    preg_match('#<script type="application/ld\+json" data-inertia="structured-data">(.*?)</script>#s', (string) $response->getContent(), $match);
    $graph = json_decode($match[1] ?? '{}', true)['@graph'] ?? [];

    return collect($graph)->keyBy(static fn (array $node): string => (string) $node['@type'])->all();
}
