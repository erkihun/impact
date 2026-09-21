<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Jobs\Media\DeleteMediaObjectsJob;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\MediaAsset;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

function makeApplication(): Application
{
    $vacancy = Vacancy::query()->create([
        'reference_no' => 'VAC-OPS-001',
        'status' => 'published',
        'title' => 'Senior Consultant',
        'slug' => 'senior-consultant',
        'locale' => 'en',
        'type' => 'employment',
        'description' => 'Lead complex engagements.',
        'requirements' => 'Relevant professional experience.',
    ]);

    return Application::query()->create([
        'vacancy_id' => $vacancy->id,
        'reference_no' => 'APP-OPS-001',
        'applicant_name' => 'Applicant One',
        'email' => 'applicant@example.test',
        'status' => 'received',
        'retention_until' => now('UTC')->addYear(),
        'submitted_at' => now('UTC'),
    ]);
}

it('enforces the recruitment workflow and appends status history', function (): void {
    $officer = User::factory()->create();
    $officer->roles()->attach(Role::query()->where('code', 'recruitment_officer')->sole());
    $application = makeApplication();

    $this->actingAs($officer)->withSession(privilegedSession($officer))
        ->patch("/admin/applications/{$application->id}", [
            'status' => 'screening',
            'reason' => 'Minimum criteria confirmed.',
        ])
        ->assertRedirect();
    $this->actingAs($officer)->withSession(privilegedSession($officer))
        ->patch("/admin/applications/{$application->id}", [
            'status' => 'shortlisted',
            'reason' => 'Strong evidence against criteria.',
        ])
        ->assertRedirect();

    expect($application->refresh()->status)->toBe(ApplicationStatus::Shortlisted);
    $this->assertDatabaseCount('application_status_histories', 2);
    $this->assertDatabaseHas('audit_events', ['action' => 'application.status_updated']);
});

it('rejects invalid application transitions and direct access without HR permission', function (): void {
    $officer = User::factory()->create();
    $officer->roles()->attach(Role::query()->where('code', 'recruitment_officer')->sole());
    $application = makeApplication();

    $this->actingAs($officer)->withSession(privilegedSession($officer))
        ->patch("/admin/applications/{$application->id}", [
            'status' => 'hired',
            'reason' => 'Invalid shortcut attempt.',
        ])
        ->assertConflict()
        ->assertSee('This request conflicts with the current state.');
    $this->actingAs(User::factory()->create())
        ->get("/admin/applications/{$application->id}")
        ->assertForbidden();
});

it('revokes restricted files immediately when an application is anonymized', function (): void {
    Queue::fake();
    Storage::fake('local');
    $officer = User::factory()->create();
    $officer->roles()->attach(Role::query()->where('code', 'recruitment_officer')->sole());
    $application = makeApplication();
    $application->update([
        'status' => ApplicationStatus::Rejected,
        'phone_encrypted' => '+251900000000',
        'cover_letter_encrypted' => 'Identifying cover letter.',
    ]);
    Storage::disk('local')->put('private/applications/anonymize.pdf', 'private-cv');
    $media = MediaAsset::query()->create([
        'original_name' => 'named-candidate.pdf',
        'disk' => 'local',
        'path' => 'private/applications/anonymize.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 10,
        'sha256' => hash('sha256', 'private-cv'),
        'visibility' => 'restricted',
        'scan_status' => 'clean',
        'processing_status' => 'ready',
    ]);
    ApplicationFile::query()->create([
        'application_id' => $application->id,
        'media_asset_id' => $media->id,
        'classification' => 'cv',
    ]);

    $this->actingAs($officer)->withSession(privilegedSession($officer))
        ->patch("/admin/applications/{$application->id}", [
            'status' => 'anonymized',
            'reason' => 'Approved retention action.',
        ])
        ->assertRedirect();

    expect($application->refresh()->status)->toBe(ApplicationStatus::Anonymized)
        ->and($application->applicant_name)->toBe('Anonymized')
        ->and($application->phone_encrypted)->toBeNull()
        ->and($application->cover_letter_encrypted)->toBeNull()
        ->and($media->refresh()->scan_status->value)->toBe('deleted')
        ->and($media->original_name)->toBe('anonymized');
    $this->assertDatabaseMissing('application_files', ['application_id' => $application->id]);
    Queue::assertPushed(
        DeleteMediaObjectsJob::class,
        fn (DeleteMediaObjectsJob $job): bool => $job->mediaAssetIds === [$media->id],
    );
});
