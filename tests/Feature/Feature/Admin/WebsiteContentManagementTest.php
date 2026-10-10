<?php

declare(strict_types=1);

use App\Models\CaseStudy;
use App\Models\Event;
use App\Models\Redirect;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\LocaleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, LocaleSeeder::class]);
});

function websiteListingsUser(string $role = 'administrator'): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', $role)->sole());

    return $user;
}

/** @return array<string, string> */
function serviceForm(array $overrides = []): array
{
    return [
        'status' => 'published',
        'slug' => '',
        'name' => 'Public Finance Reform',
        'summary' => 'Budget, treasury and fiscal transparency advice for ministries and public agencies across the region.',
        'problem_statement' => 'Fiscal systems lag behind policy ambitions.',
        'approach' => 'Diagnose, co-design and implement.',
        'deliverables' => "Diagnostic report\n\n  Reform roadmap  \n",
        'benefits' => 'Credible, transparent budgets.',
        ...$overrides,
    ];
}

it('publishes a new service straight to the public services pages', function (): void {
    $admin = websiteListingsUser();

    $response = $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->post('/admin/website/services', serviceForm());

    $service = Service::query()->with('versions')->sole();
    $version = $service->versions->sole();
    $response->assertRedirect("/admin/website/services/{$service->id}/edit")
        ->assertSessionHas('status', 'Service saved. The changes are live on the website.');
    expect($service->status)->toBe('published')
        ->and($service->created_by)->toBe($admin->id)
        ->and($version->slug)->toBe('public-finance-reform')
        ->and($version->workflow_state->value)->toBe('published')
        ->and($version->deliverables)->toBe(['Diagnostic report', 'Reform roadmap']);
    $this->assertDatabaseHas('audit_events', ['action' => 'website_content.created', 'auditable_id' => $service->id]);

    $this->get('/services/public-finance-reform')->assertOk()->assertSee('Public Finance Reform');
    $this->get('/services')->assertOk()->assertSee('Public Finance Reform');
});

it('keeps draft items off the website until they are published', function (): void {
    $admin = websiteListingsUser();
    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->post('/admin/website/services', serviceForm(['status' => 'draft']))
        ->assertSessionHas('status', 'Service saved. It is not visible on the website.');

    $this->get('/services/public-finance-reform')->assertNotFound();

    $service = Service::query()->sole();
    $this->actingAs($admin)->put("/admin/website/services/{$service->id}", serviceForm(['slug' => 'public-finance-reform']))
        ->assertSessionHasNoErrors();

    $this->get('/services/public-finance-reform')->assertOk();
});

it('redirects the old web address when an editor renames a published service', function (): void {
    $version = publishService('digital-transformation', 'Digital transformation');
    $admin = websiteListingsUser();

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->put("/admin/website/services/{$version->service_id}", serviceForm(['name' => 'Digital government', 'slug' => 'Digital Government']))
        ->assertRedirect("/admin/website/services/{$version->service_id}/edit");

    expect($version->refresh()->slug)->toBe('digital-government')
        ->and($version->version_no)->toBe(1)
        ->and(Redirect::query()->where('source_path', '/services/digital-transformation')->value('destination_url'))->toBe('/services/digital-government');
    $this->get('/services/digital-transformation')->assertRedirect('/services/digital-government');
});

it('lists, searches and filters items with their live web address', function (): void {
    publishService('digital-transformation', 'Digital transformation');
    publishService('climate-finance', 'Climate finance', status: 'draft');
    publishService('retired-service', 'Retired service', status: 'archived');
    $admin = websiteListingsUser();

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->get('/admin/website/services')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Website/Collection')
            ->where('collection.key', 'services')
            ->where('items.total', 2)
            ->where('counts.archived', 1)
            ->where('items.data', fn ($items): bool => collect($items)->firstWhere('slug', 'digital-transformation')['liveUrl'] === '/services/digital-transformation'
                && collect($items)->firstWhere('slug', 'climate-finance')['liveUrl'] === null));

    $this->actingAs($admin)->get('/admin/website/services?q=climate')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('items.total', 1)->where('items.data.0.title', 'Climate finance'));
    $this->actingAs($admin)->get('/admin/website/services?status=archived')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('items.total', 1)->where('items.data.0.title', 'Retired service'));
});

it('shows only the collections a role may manage', function (): void {
    $recruiter = websiteListingsUser('recruitment_officer');
    $session = privilegedSession($recruiter);

    $this->actingAs($recruiter)->withSession($session)->get('/admin/website')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Website/Index')
            ->has('collections', 1)
            ->where('collections.0.key', 'vacancies')
            ->where('experts', null));
    $this->actingAs($recruiter)->get('/admin/website/vacancies')->assertOk();
    $this->actingAs($recruiter)->get('/admin/website/services')->assertForbidden();
    $this->actingAs($recruiter)->post('/admin/website/services', serviceForm())->assertForbidden();
    $this->actingAs($recruiter)->get('/admin/website/unknown')->assertNotFound();
    expect(Service::query()->count())->toBe(0);

    $this->actingAs(User::factory()->create())->get('/admin/website')->assertForbidden();
});

it('refuses to publish case studies without client consent', function (): void {
    $admin = websiteListingsUser();
    $form = [
        'status' => 'published', 'slug' => 'national-delivery-unit', 'title' => 'A national delivery unit',
        'challenge' => 'Slow decisions.', 'approach' => 'A delivery rhythm.', 'outcomes' => 'Faster, shared decisions across agencies.',
    ];

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->post('/admin/website/case-studies', [...$form, 'client_display_mode' => 'named'])
        ->assertSessionHasErrors(['client_consent_at' => 'Record when the client agreed to be named before publishing.']);
    $this->actingAs($admin)->post('/admin/website/case-studies', [...$form, 'client_display_mode' => 'confidential'])
        ->assertSessionHasErrors('client_display_mode');
    expect(CaseStudy::query()->count())->toBe(0);

    $this->actingAs($admin)->post('/admin/website/case-studies', [...$form, 'client_display_mode' => 'named', 'client_consent_at' => '2026-09-01T09:00'])
        ->assertSessionHasNoErrors();
    $this->get('/case-studies/national-delivery-unit')->assertOk();
});

it('stores event times in UTC from the event timezone and shows them back in that timezone', function (): void {
    $admin = websiteListingsUser();
    $form = [
        'status' => 'registration_open', 'slug' => '', 'title' => 'Delivery leadership forum',
        'description' => 'A practical session on delivery routines.', 'format' => 'hybrid', 'venue' => 'Addis Ababa',
        'starts_at' => '2026-12-01T10:00', 'ends_at' => '2026-12-01T12:30', 'timezone' => 'Africa/Addis_Ababa',
        'capacity' => '40', 'registration_closes_at' => '',
    ];

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->post('/admin/website/events', [...$form, 'ends_at' => '2026-12-01T09:00'])
        ->assertSessionHasErrors(['ends_at' => 'The event must end after it starts.']);
    $this->actingAs($admin)->post('/admin/website/events', [...$form, 'timezone' => 'Mars/Olympus'])
        ->assertSessionHasErrors('timezone');

    $this->actingAs($admin)->post('/admin/website/events', $form)->assertSessionHasNoErrors();

    $event = Event::query()->sole();
    expect($event->starts_at->utc()->format('Y-m-d H:i'))->toBe('2026-12-01 07:00')
        ->and($event->slug)->toBe('delivery-leadership-forum')
        ->and($event->locale)->toBe('en')
        ->and($event->capacity)->toBe(40);
    $this->get('/events/delivery-leadership-forum')->assertOk();

    // New events default to the organization timezone setting.
    $this->actingAs($admin)->get('/admin/website/events/create')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('values.timezone', config('app.timezone')));

    $this->actingAs($admin)->get("/admin/website/events/{$event->id}/edit")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Website/Edit')
            ->where('values.starts_at', '2026-12-01T10:00')
            ->where('values.timezone', 'Africa/Addis_Ababa')
            ->where('item.liveUrl', '/events/delivery-leadership-forum'));
});

it('gives new vacancies a reference number and publishes them on the careers page', function (): void {
    $recruiter = websiteListingsUser('recruitment_officer');

    $this->actingAs($recruiter)->withSession(privilegedSession($recruiter))
        ->post('/admin/website/vacancies', [
            'status' => 'published', 'slug' => '', 'title' => 'Senior Policy Consultant', 'type' => 'full_time',
            'location' => 'Addis Ababa', 'description' => 'Lead policy engagements.', 'requirements' => 'Postgraduate degree.',
            'opens_at' => '', 'closes_at' => '2026-12-31T17:00', 'application_limit' => '',
        ])
        ->assertSessionHasNoErrors();

    $vacancy = Vacancy::query()->sole();
    expect($vacancy->reference_no)->toStartWith('VAC-')
        ->and($vacancy->application_limit)->toBeNull();
    $this->get('/careers/senior-policy-consultant')->assertOk()->assertSee('Senior Policy Consultant');
});

it('opens a blank form for every collection with sensible defaults', function (string $collection, string $field, string $default): void {
    $admin = websiteListingsUser();

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->get("/admin/website/{$collection}/create")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Website/Edit')
            ->where('collection.key', $collection)
            ->where('item', null)
            ->where('values.status', 'draft')
            ->where("values.{$field}", $default));
})->with([
    'services' => ['services', 'deliverables', ''],
    'industries' => ['industries', 'name', ''],
    'case studies' => ['case-studies', 'client_display_mode', 'anonymized'],
    'insights' => ['insights', 'primary_media_id', ''],
    'events' => ['events', 'format', 'physical'],
    'vacancies' => ['vacancies', 'type', 'full_time'],
]);

it('opens the editor for an existing item of every collection', function (string $collection, Closure $publish): void {
    $admin = websiteListingsUser();
    $record = $publish();
    $id = $record->getAttribute(match ($collection) {
        'services' => 'service_id',
        'industries' => 'industry_id',
        'case-studies' => 'case_study_id',
        'insights' => 'insight_id',
        default => 'id',
    });

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->get("/admin/website/{$collection}/{$id}/edit")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Website/Edit')
            ->where('item.id', (string) $id)
            ->where('values.slug', $record->getAttribute('slug')));
})->with([
    'services' => ['services', fn () => publishService('strategy-transformation')],
    'industries' => ['industries', fn () => publishIndustry('public-institutions')],
    'case studies' => ['case-studies', fn () => publishCaseStudy('delivery-unit')],
    'insights' => ['insights', fn () => publishInsight('strategy-reality')],
    'events' => ['events', fn () => publishEvent('leadership-forum')],
    'vacancies' => ['vacancies', fn () => publishVacancy('senior-consultant')],
]);

it('rejects reserved and duplicate web addresses', function (): void {
    publishService('digital-transformation');
    $admin = websiteListingsUser();

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->post('/admin/website/services', serviceForm(['slug' => 'digital-transformation']))
        ->assertSessionHasErrors('slug');
    $this->actingAs($admin)->post('/admin/website/services', serviceForm(['slug' => 'admin']))
        ->assertSessionHasErrors('slug');

    expect(Service::query()->count())->toBe(1);
});
