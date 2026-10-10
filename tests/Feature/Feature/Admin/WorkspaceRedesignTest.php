<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\EngagementSubmission;
use App\Models\MediaAsset;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\LocaleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, LocaleSeeder::class]);
});

function workspaceUser(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', $role)->sole());

    return $user;
}

function workspaceRequest(string $status = 'received', array $attributes = []): EngagementSubmission
{
    return EngagementSubmission::query()->create([
        'reference_no' => 'RFP-'.strtoupper(Str::random(8)),
        'type' => 'rfp',
        'status' => $status,
        'locale' => 'en',
        'organization_name' => 'Regional Water Bureau',
        'contact_name' => 'Meseret Wolde',
        'email' => 'm.wolde@example.org',
        'description_encrypted' => 'We need a partner for a five-year utility strategy.',
        'retention_until' => now()->addYear(),
        'submitted_at' => now(),
        ...$attributes,
    ]);
}

function workspaceApplication(string $status = 'received', string $name = 'Hanna Girma'): Application
{
    $vacancy = Vacancy::query()->firstOrCreate(['slug' => 'senior-consultant'], [
        'reference_no' => 'VAC-WS-001', 'status' => 'published', 'title' => 'Senior Consultant', 'locale' => 'en',
        'type' => 'full_time', 'description' => 'Lead engagements.', 'requirements' => 'Experience.',
    ]);

    return Application::query()->create([
        'vacancy_id' => $vacancy->id,
        'reference_no' => 'APP-'.strtoupper(Str::random(8)),
        'applicant_name' => $name,
        'email' => Str::slug($name).'@example.test',
        'status' => $status,
        'retention_until' => now('UTC')->addYear(),
        'submitted_at' => now('UTC'),
    ]);
}

it('shows each person what needs them today and badges the inbox', function (): void {
    $admin = workspaceUser('super_administrator');
    workspaceRequest();
    workspaceRequest('assigned', ['assigned_to' => $admin->id]);
    workspaceApplication();

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Dashboard')
            ->where('workspace.inbox.requests', 1)
            ->where('workspace.inbox.applications', 1)
            ->where('attention', fn ($tiles): bool => collect($tiles)->pluck('key')->all() === ['review', 'requests', 'applications', 'scheduled']
                && collect($tiles)->firstWhere('key', 'requests')['count'] === 1
                && collect($tiles)->firstWhere('key', 'requests')['hint'] === '1 not assigned to anyone')
            ->where('queue', fn ($queue): bool => collect($queue)->pluck('title')->contains('Regional Water Bureau')
                && collect($queue)->pluck('title')->contains('Senior Consultant'))
            ->where('health', ['checked' => false]));

    $recruiter = workspaceUser('recruitment_officer');
    $this->actingAs($recruiter)->withSession(privilegedSession($recruiter))
        ->get('/admin')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('attention', fn ($tiles): bool => collect($tiles)->pluck('key')->all() === ['applications'])
            ->where('health', null)
            ->where('activity', [])
            ->missing('workspace.inbox.requests'));
});

it('finds records for the command palette only where the person may look', function (): void {
    publishService('strategy-transformation', 'Strategy and transformation');
    publishVacancy('strategy-analyst');
    Vacancy::query()->where('slug', 'strategy-analyst')->update(['title' => 'Strategy Analyst']);
    $admin = workspaceUser('super_administrator');

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->getJson('/admin/search?q=strategy')
        ->assertOk()
        ->assertJsonFragment(['label' => 'Strategy and transformation', 'group' => 'Listings', 'hint' => 'Service'])
        ->assertJsonFragment(['label' => 'Strategy Analyst', 'hint' => 'Vacancy']);

    $recruiter = workspaceUser('recruitment_officer');
    $results = $this->actingAs($recruiter)->withSession(privilegedSession($recruiter))
        ->getJson('/admin/search?q=strategy')
        ->assertOk()
        ->json('results');
    expect(collect($results)->pluck('label')->all())->toBe(['Strategy Analyst']);

    $this->actingAs($admin)->getJson('/admin/search?q=s')->assertExactJson(['results' => []]);
});

it('groups requests into inbox tabs and keeps the list beside the open request', function (): void {
    $admin = workspaceUser('super_administrator');
    $new = workspaceRequest();
    $waiting = workspaceRequest('awaiting_client', ['organization_name' => 'Horizon Foundation']);

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->get('/admin/engagement')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Engagement/Index')
            ->where('filters.view', 'new')
            ->where('counts', ['new' => 1, 'progress' => 0, 'waiting' => 1, 'closed' => 0])
            ->where('submissions.data.0.id', $new->id)
            ->has('submissions.data', 1));

    $this->actingAs($admin)->get("/admin/engagement/{$waiting->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Engagement/Show')
            ->where('filters.view', 'waiting')
            ->where('submission.id', $waiting->id)
            ->where('submissions.data.0.organization_name', 'Horizon Foundation'));
});

it('lays applications out as a hiring board with each card’s next steps', function (): void {
    $admin = workspaceUser('super_administrator');
    $received = workspaceApplication();
    workspaceApplication('rejected', 'Samuel Alemu');

    $this->actingAs($admin)->withSession(privilegedSession($admin))
        ->get('/admin/applications')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Applications/Index')
            ->where('columns.0.status', 'received')
            ->where('columns.0.items.0.id', $received->id)
            ->where('columns.0.items.0.moves', fn ($moves): bool => in_array('screening', collect($moves)->all(), true))
            ->has('columns', 6)
            ->where('closedCount', 1));

    $this->actingAs($admin)->get('/admin/applications?closed=1')
        ->assertInertia(fn (AssertableInertia $page) => $page->has('columns', 9));
});

it('lets media editors fix a file’s title and description', function (): void {
    $editor = workspaceUser('editor');
    $asset = MediaAsset::query()->create([
        'original_name' => 'highlands.jpg', 'disk' => 'public', 'path' => 'media/2026/10/highlands.jpg',
        'mime_type' => 'image/jpeg', 'size_bytes' => 1024, 'visibility' => 'public',
        'scan_status' => 'clean', 'processing_status' => 'ready', 'title' => 'Old title', 'sha256' => hash('sha256', 'highlands'),
    ]);

    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->patch("/admin/media/{$asset->id}", ['title' => 'Ethiopian highlands', 'alt_text' => 'Terraced green hills at dawn.'])
        ->assertRedirect()
        ->assertSessionHas('status', 'File details saved.');

    expect($asset->refresh()->alt_text)->toBe('Terraced green hills at dawn.');
    $this->assertDatabaseHas('audit_events', ['action' => 'media.details_updated', 'auditable_id' => $asset->id]);

    $this->actingAs($editor)->get('/admin/media')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('assets.data.0.title', 'Ethiopian highlands')
            ->where('assets.data.0.needs_approval', false)
            ->where('counts.images', 1));

    $this->actingAs(workspaceUser('reviewer'))->patch("/admin/media/{$asset->id}", ['title' => 'Nope'])->assertForbidden();
});

it('writes the activity log as readable sentences and filters by area', function (): void {
    $admin = workspaceUser('super_administrator');
    $this->actingAs($admin)->withSession(privilegedSession($admin))->post('/admin/website/services', [
        'status' => 'published', 'slug' => '', 'name' => 'Public Finance Reform',
        'summary' => 'Budget, treasury and fiscal transparency advice for ministries and public agencies across the region.',
        'deliverables' => 'Diagnostic',
    ])->assertSessionHasNoErrors();

    $this->actingAs($admin)->get('/admin/audit-events?action=website_content.')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Audit/Index')
            ->has('events.data', 1)
            ->where('events.data.0.actor', $admin->name)
            ->where('events.data.0.text', 'created the service “Public Finance Reform” as published')
            ->where('events.data.0.action', 'website_content.created')
            ->where('events.data.0.href', fn (string $href): bool => str_starts_with($href, '/admin/website/services/')));

    expect(AuditEvent::query()->where('action', 'website_content.created')->value('metadata'))->toMatchArray(['title' => 'Public Finance Reform']);
});
