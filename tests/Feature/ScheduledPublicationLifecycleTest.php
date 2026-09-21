<?php

declare(strict_types=1);

use App\Contracts\AuditRecorder;
use App\Jobs\Content\PublishScheduledContentJob;
use App\Models\ContentItem;
use App\Models\PublicationSchedule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

it('creates a schedule and idempotently publishes then unpublishes with workflow evidence', function (): void {
    $author = User::factory()->create();
    $author->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $reviewer = User::factory()->create();
    $reviewer->roles()->attach(Role::query()->where('code', 'reviewer')->sole());
    $publisher = User::factory()->create();
    $publisher->roles()->attach(Role::query()->where('code', 'publisher')->sole());

    $this->actingAs($author)->withSession(privilegedSession($author))->post('/admin/content', [
        'type' => 'page',
        'locale' => 'en',
        'slug' => 'scheduled-evidence',
        'title' => 'Scheduled evidence',
        'summary' => 'A scheduled content lifecycle.',
        'body' => 'This content is approved for a controlled publication window.',
    ])->assertRedirect();
    $content = ContentItem::query()->with('currentVersion')->sole();
    $transitionUrl = "/admin/content/{$content->id}/transitions";

    $this->actingAs($author)->withSession(privilegedSession($author))->post($transitionUrl, [
        'content_version_id' => $content->current_version_id,
        'to' => 'in_review',
        'note' => 'Ready for independent review.',
    ])->assertRedirect();
    $this->actingAs($reviewer)->withSession(privilegedSession($reviewer))->post($transitionUrl, [
        'content_version_id' => $content->current_version_id,
        'to' => 'approved',
        'note' => 'Accuracy and accessibility checks complete.',
    ])->assertRedirect();

    $publishAt = now('UTC')->addMinute();
    $unpublishAt = now('UTC')->addMinutes(3);
    $this->actingAs($publisher)->withSession(privilegedSession($publisher))->post($transitionUrl, [
        'content_version_id' => $content->current_version_id,
        'to' => 'scheduled',
        'publish_at' => $publishAt->toDateTimeString(),
        'unpublish_at' => $unpublishAt->toDateTimeString(),
        'note' => 'Approved publication window.',
    ])->assertRedirect();

    $schedule = PublicationSchedule::query()->sole();
    expect($content->refresh()->status->value)->toBe('scheduled')
        ->and($schedule->status)->toBe('pending')
        ->and($schedule->created_by)->toBe($publisher->id);

    $this->travelTo($publishAt->addSecond());
    $publishJob = new PublishScheduledContentJob($schedule->id);
    $publishJob->handle(app(AuditRecorder::class));
    expect($content->refresh()->status->value)->toBe('published')
        ->and($schedule->refresh()->status)->toBe('published');
    $this->assertDatabaseHas('workflow_events', [
        'content_item_id' => $content->id,
        'from_state' => 'scheduled',
        'to_state' => 'published',
        'actor_id' => $publisher->id,
    ]);

    $this->travelTo($unpublishAt->addSecond());
    $unpublishJob = new PublishScheduledContentJob($schedule->id);
    $unpublishJob->handle(app(AuditRecorder::class));
    expect($content->refresh()->status->value)->toBe('unpublished')
        ->and($schedule->refresh()->status)->toBe('completed');
    $this->assertDatabaseHas('workflow_events', [
        'content_item_id' => $content->id,
        'from_state' => 'published',
        'to_state' => 'unpublished',
        'actor_id' => $publisher->id,
    ]);
    $this->assertDatabaseHas('audit_events', ['action' => 'content.published_by_schedule']);
    $this->assertDatabaseHas('audit_events', ['action' => 'content.unpublished_by_schedule']);

    $eventCount = $content->workflowEvents()->count();
    $unpublishJob->handle(app(AuditRecorder::class));
    expect($content->workflowEvents()->count())->toBe($eventCount);
});

it('rejects a scheduled transition without a future publication time', function (): void {
    $publisher = User::factory()->create();
    $publisher->roles()->attach(Role::query()->where('code', 'publisher')->sole());
    $content = ContentItem::query()->create([
        'type' => 'page',
        'owner_id' => $publisher->id,
        'status' => 'approved',
    ]);
    $version = $content->versions()->create([
        'locale' => 'en',
        'version_no' => 1,
        'slug' => 'missing-schedule-time',
        'title' => 'Missing schedule time',
        'body' => ['content' => 'Approved content.'],
        'workflow_state' => 'approved',
        'created_by' => $publisher->id,
        'content_hash' => hash('sha256', 'Approved content.'),
    ]);
    $content->update(['current_version_id' => $version->id]);

    $this->actingAs($publisher)->withSession(privilegedSession($publisher))
        ->from(route('admin.content.show', $content))
        ->post("/admin/content/{$content->id}/transitions", [
            'content_version_id' => $version->id,
            'to' => 'scheduled',
        ])
        ->assertRedirect(route('admin.content.show', $content))
        ->assertSessionHasErrors('publish_at');

    expect($content->refresh()->status->value)->toBe('approved');
    $this->assertDatabaseCount('publication_schedules', 0);
});
