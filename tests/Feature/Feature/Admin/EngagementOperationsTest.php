<?php

declare(strict_types=1);

use App\Models\EngagementSubmission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

it('enforces the submission lifecycle and records assignment history', function (): void {
    $officer = User::factory()->create();
    $officer->roles()->attach(Role::query()->where('code', 'engagement_officer')->sole());
    $assignee = User::factory()->create();
    $submission = EngagementSubmission::query()->create([
        'reference_no' => 'CONS-TEST-001',
        'type' => 'consultation',
        'status' => 'received',
        'locale' => 'en',
        'contact_name' => 'Example Contact',
        'email' => 'contact@example.com',
        'description_encrypted' => 'A sufficiently detailed consultation request.',
        'retention_until' => now()->addYear(),
        'submitted_at' => now(),
    ]);

    $this->actingAs($officer)->withSession(privilegedSession($officer))->patch("/admin/engagement/{$submission->id}", [
        'status' => 'triaged',
        'note' => 'Relevant and complete.',
    ])->assertRedirect();
    $this->actingAs($officer)->withSession(privilegedSession($officer))->patch("/admin/engagement/{$submission->id}", [
        'status' => 'assigned',
        'assigned_to' => $assignee->id,
        'note' => 'Assigned by sector fit.',
    ])->assertRedirect();

    expect($submission->refresh()->status->value)->toBe('assigned')
        ->and($submission->assigned_to)->toBe($assignee->id);
    $this->assertDatabaseCount('engagement_submission_histories', 2);
    $this->assertDatabaseHas('audit_events', ['action' => 'engagement.status_updated']);
});

it('rejects lifecycle shortcuts', function (): void {
    $officer = User::factory()->create();
    $officer->roles()->attach(Role::query()->where('code', 'engagement_officer')->sole());
    $submission = EngagementSubmission::query()->create([
        'reference_no' => 'CONS-TEST-002',
        'type' => 'contact',
        'status' => 'received',
        'locale' => 'en',
        'contact_name' => 'Example Contact',
        'email' => 'contact@example.com',
        'description_encrypted' => 'A sufficiently detailed contact request.',
        'retention_until' => now()->addYear(),
        'submitted_at' => now(),
    ]);

    $this->actingAs($officer)->withSession(privilegedSession($officer))
        ->patch("/admin/engagement/{$submission->id}", ['status' => 'closed'])
        ->assertConflict()
        ->assertSee('This request conflicts with the current state.');
    expect($submission->refresh()->status->value)->toBe('received');
});
