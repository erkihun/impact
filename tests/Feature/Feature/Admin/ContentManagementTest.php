<?php

declare(strict_types=1);

use App\Models\ContentItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

it('creates a versioned draft and moves it into review through the workflow action', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());

    $response = $this->actingAs($editor)->withSession(privilegedSession($editor))->post('/admin/content', [
        'type' => 'page',
        'locale' => 'en',
        'slug' => 'our-quality-commitment',
        'title' => 'Our quality commitment',
        'summary' => 'How quality is governed.',
        'body' => 'Quality is designed into every engagement.',
    ]);
    $content = ContentItem::query()->with('currentVersion')->sole();
    $response->assertRedirect(route('admin.content.show', $content));
    expect($content->status->value)->toBe('draft')
        ->and($content->currentVersion->content_hash)->toHaveLength(64);

    $this->actingAs($editor)->post("/admin/content/{$content->id}/transitions", [
        'content_version_id' => $content->currentVersion->id,
        'to' => 'in_review',
        'note' => 'Ready for review.',
    ])->assertRedirect();

    expect($content->refresh()->status->value)->toBe('in_review');
    $this->assertDatabaseCount('workflow_events', 2);
    $this->assertDatabaseHas('audit_events', ['action' => 'content.transition.in_review']);
});

it('denies content administration without permissions', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/admin/content')
        ->assertForbidden();
});

it('prevents the active revision author from approving their own work', function (): void {
    $owner = User::factory()->create();
    $authorReviewer = User::factory()->create();
    $authorReviewer->roles()->attach(Role::query()->whereIn('code', ['editor', 'reviewer'])->get());
    $content = ContentItem::query()->create([
        'type' => 'page',
        'owner_id' => $owner->id,
        'status' => 'in_review',
    ]);
    $version = $content->versions()->create([
        'locale' => 'en',
        'version_no' => 1,
        'slug' => 'independent-approval',
        'title' => 'Independent approval',
        'body' => ['content' => 'This revision needs independent approval.'],
        'workflow_state' => 'in_review',
        'created_by' => $authorReviewer->id,
        'content_hash' => hash('sha256', 'This revision needs independent approval.'),
    ]);
    $content->update(['current_version_id' => $version->id]);

    $this->actingAs($authorReviewer)->withSession(privilegedSession($authorReviewer))
        ->post("/admin/content/{$content->id}/transitions", [
            'content_version_id' => $version->id,
            'to' => 'approved',
            'note' => 'Attempted self approval.',
        ])
        ->assertForbidden();

    expect($content->refresh()->status->value)->toBe('in_review');
    $this->assertDatabaseCount('workflow_events', 0);
});

it('lets a contributor publish content directly when creating it', function (): void {
    $contributor = User::factory()->create();
    $contributor->roles()->attach(Role::query()->where('code', 'contributor')->sole());

    $response = $this->actingAs($contributor)->withSession(privilegedSession($contributor))->post('/admin/content', [
        'type' => 'page',
        'locale' => 'en',
        'slug' => 'published-on-create',
        'title' => 'Published on create',
        'summary' => 'Goes live straight away.',
        'body' => 'No review step is needed for this post.',
        'publish' => true,
    ]);

    $content = ContentItem::query()->with('currentVersion')->sole();
    $response->assertRedirect(route('admin.content.show', $content));
    expect($content->status->value)->toBe('published')
        ->and($content->published_at)->not->toBeNull()
        ->and($content->currentVersion->workflow_state->value)->toBe('published');
    $this->assertDatabaseCount('workflow_events', 2);
    $this->assertDatabaseHas('workflow_events', ['to_state' => 'published', 'note' => 'published_on_create']);
    $this->assertDatabaseHas('audit_events', ['action' => 'content.transition.published']);
});

it('lets a contributor publish an existing draft without review', function (): void {
    $contributor = User::factory()->create();
    $contributor->roles()->attach(Role::query()->where('code', 'contributor')->sole());

    $this->actingAs($contributor)->withSession(privilegedSession($contributor))->post('/admin/content', [
        'type' => 'page', 'locale' => 'en', 'slug' => 'draft-first', 'title' => 'Draft first', 'body' => 'Saved as a draft.',
    ])->assertRedirect();
    $content = ContentItem::query()->sole();
    expect($content->status->value)->toBe('draft');

    $this->actingAs($contributor)->withSession(privilegedSession($contributor))
        ->get("/admin/content/{$content->id}")
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->where('allowedTransitions.0', 'published'));

    $this->actingAs($contributor)->withSession(privilegedSession($contributor))
        ->post("/admin/content/{$content->id}/transitions", [
            'content_version_id' => $content->current_version_id,
            'to' => 'published',
            'note' => 'Publishing straight from the draft.',
        ])->assertRedirect()->assertSessionHasNoErrors();

    expect($content->refresh()->status->value)->toBe('published');
});

it('publishes a new revision directly from the edit form', function (): void {
    $contributor = User::factory()->create();
    $contributor->roles()->attach(Role::query()->where('code', 'contributor')->sole());

    $this->actingAs($contributor)->withSession(privilegedSession($contributor))->post('/admin/content', [
        'type' => 'page', 'locale' => 'en', 'slug' => 'revise-and-publish', 'title' => 'First title', 'body' => 'First body.',
    ])->assertRedirect();
    $content = ContentItem::query()->sole();

    $this->actingAs($contributor)->withSession(privilegedSession($contributor))
        ->patch("/admin/content/{$content->id}", [
            'expected_current_version_id' => $content->current_version_id,
            'title' => 'Second title',
            'body' => 'Second body.',
            'publish' => true,
        ])->assertRedirect(route('admin.content.show', $content))->assertSessionHasNoErrors();

    $content->refresh()->load('currentVersion');
    expect($content->status->value)->toBe('published')
        ->and($content->currentVersion->version_no)->toBe(2)
        ->and($content->currentVersion->title)->toBe('Second title')
        ->and($content->currentVersion->workflow_state->value)->toBe('published');
    $this->assertDatabaseHas('workflow_events', ['to_state' => 'published', 'note' => 'published_on_save']);
});

it('keeps publication closed to users who can neither create nor publish content', function (): void {
    $owner = User::factory()->create();
    $reviewer = User::factory()->create();
    $reviewer->roles()->attach(Role::query()->where('code', 'reviewer')->sole());
    $content = ContentItem::query()->create(['type' => 'page', 'owner_id' => $owner->id, 'status' => 'draft']);
    $version = $content->versions()->create([
        'locale' => 'en', 'version_no' => 1, 'slug' => 'reviewer-cannot-publish', 'title' => 'Reviewer cannot publish',
        'body' => ['content' => 'Only creators and publishers may publish.'], 'workflow_state' => 'draft',
        'created_by' => $owner->id, 'content_hash' => str_repeat('a', 64),
    ]);
    $content->update(['current_version_id' => $version->id]);

    $this->actingAs($reviewer)->withSession(privilegedSession($reviewer))
        ->post("/admin/content/{$content->id}/transitions", [
            'content_version_id' => $version->id, 'to' => 'published', 'note' => 'Trying to publish.',
        ])->assertForbidden();

    expect($content->refresh()->status->value)->toBe('draft');
});
