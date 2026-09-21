<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

it('returns a stable JSON 409 contract for invalid workflow transitions', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    $officer = User::factory()->create();
    $officer->roles()->attach(Role::query()->where('code', 'recruitment_officer')->sole());
    $vacancy = Vacancy::query()->create([
        'reference_no' => 'VAC-CONFLICT-001',
        'status' => 'published',
        'title' => 'Consultant',
        'slug' => 'consultant-conflict',
        'locale' => 'en',
        'type' => 'consulting',
        'description' => 'A documented role.',
        'requirements' => 'Relevant experience.',
    ]);
    $application = Application::query()->create([
        'vacancy_id' => $vacancy->id,
        'reference_no' => 'APP-CONFLICT-001',
        'applicant_name' => 'Example Applicant',
        'email' => 'applicant-conflict@example.test',
        'status' => 'received',
        'retention_until' => now('UTC')->addYear(),
        'submitted_at' => now('UTC'),
    ]);

    $this->actingAs($officer)
        ->withSession(privilegedSession($officer))
        ->withHeader('Accept', 'application/json')
        ->patchJson("/admin/applications/{$application->id}", [
            'status' => 'hired',
            'reason' => 'Invalid shortcut.',
        ])
        ->assertConflict()
        ->assertJsonPath('code', 'INVALID_STATE_TRANSITION')
        ->assertJsonStructure(['message', 'correlation_id']);

    expect($application->refresh()->status->value)->toBe('received');
    $this->assertDatabaseCount('application_status_histories', 0);
});
