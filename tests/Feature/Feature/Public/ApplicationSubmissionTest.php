<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\MediaAsset;
use App\Models\Vacancy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('accepts an application while keeping the CV quarantined and private', function (): void {
    Storage::fake('local');
    $vacancy = Vacancy::query()->create([
        'reference_no' => 'VAC-OPEN',
        'status' => 'published',
        'title' => 'Consultant',
        'slug' => 'consultant',
        'locale' => 'en',
        'type' => 'full_time',
        'description' => 'Role description',
        'requirements' => 'Role requirements',
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addWeek(),
    ]);

    $this->post("/en/careers/{$vacancy->slug}/applications", [
        'applicant_name' => 'Aster Bekele',
        'email' => 'aster@example.com',
        'phone' => '+251900000000',
        'cover_letter' => 'I bring relevant delivery and consulting experience.',
        'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        'privacy_acknowledged' => '1',
    ])->assertRedirect()->assertSessionHas('submission.reference');

    $application = Application::query()->sole();
    $media = MediaAsset::query()->sole();
    expect($application->cover_letter_encrypted)->toContain('relevant delivery')
        ->and($media->scan_status->value)->toBe('quarantined')
        ->and($media->visibility->value)->toBe('restricted')
        ->and($media->path)->not->toContain('cv');
    Storage::disk('local')->assertExists($media->path);
    $this->assertDatabaseCount('application_files', 1);
    $this->assertDatabaseCount('application_status_histories', 1);
    $this->assertDatabaseHas('audit_events', ['action' => 'application.received']);
});

it('rejects an expired vacancy and removes the orphaned quarantine upload', function (): void {
    Storage::fake('local');
    $vacancy = Vacancy::query()->create([
        'reference_no' => 'VAC-CLOSED',
        'status' => 'published',
        'title' => 'Closed role',
        'slug' => 'closed-role',
        'locale' => 'en',
        'type' => 'contract',
        'description' => 'Role description',
        'requirements' => 'Role requirements',
        'opens_at' => now()->subMonth(),
        'closes_at' => now()->subDay(),
    ]);

    $this->from("/en/careers/{$vacancy->slug}")
        ->post("/en/careers/{$vacancy->slug}/applications", [
            'applicant_name' => 'Aster Bekele',
            'email' => 'aster@example.com',
            'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
            'privacy_acknowledged' => '1',
        ])
        ->assertRedirect("/en/careers/{$vacancy->slug}")
        ->assertSessionHasErrors('vacancy');

    $this->assertDatabaseCount('applications', 0);
    $this->assertDatabaseCount('media_assets', 0);
});
