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
