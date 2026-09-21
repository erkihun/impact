<?php

declare(strict_types=1);

use App\Enums\ContentWorkflowState;
use App\Models\ContentItem;
use App\Models\ContentVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowEvent;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

it('creates immutable revisions and returns a stable conflict for stale edits', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $content = createVersionedContent($this, $editor);
    $versionOne = $content->currentVersion;

    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->patch(route('admin.content.update', $content), [
            'expected_current_version_id' => $versionOne->id,
            'title' => 'Updated quality commitment',
            'summary' => 'Updated summary.',
            'body' => 'Updated body with immutable history.',
        ])
        ->assertRedirect(route('admin.content.show', $content));

    $content->refresh();
    $versionTwo = $content->currentVersion;
    expect($versionTwo->id)->not->toBe($versionOne->id)
        ->and($versionTwo->version_no)->toBe(2)
        ->and($versionTwo->slug)->toBe($versionOne->slug)
        ->and($versionOne->refresh()->title)->toBe('Quality commitment')
        ->and(ContentVersion::query()->where('content_item_id', $content->id)->count())->toBe(2);

    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->patchJson(route('admin.content.update', $content), [
            'expected_current_version_id' => $versionOne->id,
            'title' => 'Stale overwrite',
            'body' => 'This update must be rejected.',
        ])
        ->assertConflict()
        ->assertJsonPath('code', 'CONTENT_VERSION_CONFLICT')
        ->assertJsonPath('current_version_id', $versionTwo->id);

    expect(ContentVersion::query()->where('content_item_id', $content->id)->count())->toBe(2);
});

it('serves signed authenticated previews without cache or indexing', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $content = createVersionedContent($this, $editor);
    $version = $content->currentVersion;
    $url = URL::temporarySignedRoute(
        'admin.content.preview',
        now()->addMinutes(10),
        ['content' => $content, 'version' => $version],
    );

    $response = $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->get($url);
    $response
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertSee('Private preview.');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');

    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->get(route('admin.content.preview', [$content, $version]))
        ->assertForbidden();
});

it('restores an approved historical version as a new draft revision', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $publisher = User::factory()->create();
    $publisher->roles()->attach(Role::query()->where('code', 'publisher')->sole());
    $content = createVersionedContent($this, $editor);
    $versionOne = $content->currentVersion;
    WorkflowEvent::query()->create([
        'content_item_id' => $content->id,
        'content_version_id' => $versionOne->id,
        'from_state' => 'in_review',
        'to_state' => 'approved',
        'actor_id' => $publisher->id,
        'note' => 'Approved historical version.',
        'correlation_id' => (string) str()->uuid7(),
    ]);

    $this->actingAs($editor)->withSession(privilegedSession($editor))
        ->patch(route('admin.content.update', $content), [
            'expected_current_version_id' => $versionOne->id,
            'title' => 'Risky new revision',
            'body' => 'Revision to roll back.',
        ])->assertRedirect();
    $versionTwo = $content->refresh()->currentVersion;

    $this->actingAs($publisher)->withSession(privilegedSession($publisher))
        ->post(route('admin.content.rollback', $content), [
            'expected_current_version_id' => $versionTwo->id,
            'source_version_id' => $versionOne->id,
            'reason' => 'Restore the approved baseline after regression.',
        ])->assertRedirect(route('admin.content.show', $content));

    $restored = $content->refresh()->currentVersion;
    expect($restored->id)->not->toBe($versionOne->id)
        ->and($restored->version_no)->toBe(3)
        ->and($restored->title)->toBe($versionOne->title)
        ->and($restored->body)->toBe($versionOne->body)
        ->and($content->status)->toBe(ContentWorkflowState::Draft);
    $this->assertDatabaseHas('audit_events', ['action' => 'content.rollback_revision_created']);
});

function createVersionedContent(TestCase $testCase, User $editor): ContentItem
{
    $response = $testCase->actingAs($editor)->withSession(privilegedSession($editor))
        ->post(route('admin.content.store'), [
            'type' => 'page',
            'locale' => 'en',
            'slug' => 'quality-commitment-'.str()->random(6),
            'title' => 'Quality commitment',
            'summary' => 'Original summary.',
            'body' => 'Original approved body.',
        ]);
    $response->assertRedirect();

    return ContentItem::query()->with('currentVersion')->latest()->firstOrFail();
}
