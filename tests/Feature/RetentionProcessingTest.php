<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Enums\RetentionRunMode;
use App\Enums\SubmissionStatus;
use App\Models\Application;
use App\Models\EngagementSubmission;
use App\Models\LegalHold;
use App\Models\RetentionRun;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

it('reports due records without modifying them in dry-run mode', function (): void {
    $submission = dueRetentionSubmission('RET-DRY-001');

    $this->artisan('impact:retention:inspect')->assertSuccessful();

    expect($submission->refresh()->contact_name)->toBe('Retention Contact')
        ->and($submission->retention_processed_at)->toBeNull();
    $run = RetentionRun::query()->sole();
    expect($run->mode)->toBe(RetentionRunMode::DryRun)
        ->and($run->candidate_count)->toBe(1)
        ->and($run->processed_count)->toBe(0);
});

it('requires explicit authorized approval for destructive retention execution', function (): void {
    dueRetentionSubmission('RET-AUTH-001');
    $unauthorized = User::factory()->create();

    $this->artisan('impact:retention:inspect', [
        '--execute' => true,
        '--approved-by' => $unauthorized->id,
    ])->assertFailed();

    expect(RetentionRun::query()->count())->toBe(0);
});

it('anonymizes due records in bounded runs while excluding active legal holds', function (): void {
    $approver = User::factory()->create();
    $approver->roles()->attach(Role::query()->where('code', 'administrator')->sole());
    $submission = dueRetentionSubmission('RET-EXEC-001');
    $application = dueRetentionApplication('RET-APP-001');
    $hold = LegalHold::query()->create([
        'holdable_type' => Application::class,
        'holdable_id' => $application->id,
        'reason' => 'Active employment dispute.',
        'placed_by' => $approver->id,
        'placed_at' => now('UTC'),
    ]);

    $this->artisan('impact:retention:inspect', [
        '--execute' => true,
        '--approved-by' => $approver->id,
        '--limit' => 10,
    ])->assertSuccessful();

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Closed)
        ->and($submission->contact_name)->toBe('Anonymized')
        ->and($submission->phone_encrypted)->toBeNull()
        ->and($submission->retention_processed_at)->not->toBeNull()
        ->and($application->refresh()->status)->toBe(ApplicationStatus::Received)
        ->and($application->applicant_name)->toBe('Retention Applicant');
    $firstRun = RetentionRun::query()->latest('started_at')->firstOrFail();
    expect($firstRun->processed_count)->toBe(1)
        ->and($firstRun->legal_hold_count)->toBe(1)
        ->and($firstRun->failure_count)->toBe(0);

    $hold->update([
        'released_by' => $approver->id,
        'released_at' => now('UTC'),
    ]);
    $this->artisan('impact:retention:inspect', [
        '--execute' => true,
        '--approved-by' => $approver->id,
        '--limit' => 10,
    ])->assertSuccessful();

    expect($application->refresh()->status)->toBe(ApplicationStatus::Anonymized)
        ->and($application->applicant_name)->toBe('Anonymized')
        ->and($application->phone_encrypted)->toBeNull()
        ->and($application->retention_processed_at)->not->toBeNull();
    $this->assertDatabaseHas('audit_events', ['action' => 'retention.record_anonymized']);
    $this->assertDatabaseCount('retention_runs', 2);
});

function dueRetentionSubmission(string $reference): EngagementSubmission
{
    return EngagementSubmission::query()->create([
        'reference_no' => $reference,
        'type' => 'consultation',
        'status' => 'received',
        'locale' => 'en',
        'contact_name' => 'Retention Contact',
        'organization_name' => 'Identifying Organization',
        'email' => 'retention@example.test',
        'phone_encrypted' => '+251900000000',
        'description_encrypted' => 'Identifying details.',
        'retention_until' => now('UTC')->subDay(),
        'submitted_at' => now('UTC')->subYears(3),
    ]);
}

function dueRetentionApplication(string $reference): Application
{
    $vacancy = Vacancy::query()->create([
        'reference_no' => 'VAC-'.$reference,
        'status' => 'closed',
        'title' => 'Archived vacancy',
        'slug' => str($reference)->lower()->toString(),
        'locale' => 'en',
        'type' => 'employment',
        'description' => 'Archived.',
        'requirements' => 'Archived.',
    ]);

    return Application::query()->create([
        'vacancy_id' => $vacancy->id,
        'reference_no' => $reference,
        'applicant_name' => 'Retention Applicant',
        'email' => 'applicant@example.test',
        'phone_encrypted' => '+251911111111',
        'status' => 'received',
        'retention_until' => now('UTC')->subDay(),
        'submitted_at' => now('UTC')->subYears(3),
    ]);
}
