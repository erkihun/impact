<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\MediaAsset;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    Storage::fake('local');
});

it('audits an authorized signed private-media download', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    Storage::disk('local')->put('private/media/report.pdf', 'safe-pdf');
    $media = MediaAsset::query()->create([
        'original_name' => 'report.pdf',
        'disk' => 'local',
        'path' => 'private/media/report.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 8,
        'sha256' => hash('sha256', 'safe-pdf'),
        'visibility' => 'private',
        'scan_status' => 'clean',
        'processing_status' => 'ready',
        'uploaded_by' => $editor->id,
    ]);

    $this->actingAs($editor)
        ->get(URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['media' => $media]))
        ->assertOk()
        ->assertHeader('x-content-type-options', 'nosniff');

    $this->assertDatabaseHas('audit_events', [
        'action' => 'private_file.downloaded',
        'auditable_type' => MediaAsset::class,
        'auditable_id' => $media->id,
        'actor_id' => $editor->id,
    ]);
});

it('audits HR access to an application file without exposing it publicly', function (): void {
    $officer = User::factory()->create();
    $officer->roles()->attach(Role::query()->where('code', 'recruitment_officer')->sole());
    $vacancy = Vacancy::query()->create([
        'reference_no' => 'VAC-DOWNLOAD-001',
        'status' => 'published',
        'title' => 'Recruitment test',
        'slug' => 'recruitment-test',
        'locale' => 'en',
        'type' => 'employment',
        'description' => 'Role details.',
        'requirements' => 'Role requirements.',
    ]);
    $application = Application::query()->create([
        'vacancy_id' => $vacancy->id,
        'reference_no' => 'APP-DOWNLOAD-001',
        'applicant_name' => 'Private Applicant',
        'email' => 'private-applicant@example.test',
        'status' => 'received',
        'retention_until' => now('UTC')->addYear(),
        'submitted_at' => now('UTC'),
    ]);
    Storage::disk('local')->put('private/applications/cv.pdf', 'safe-cv');
    $media = MediaAsset::query()->create([
        'original_name' => 'candidate-cv.pdf',
        'disk' => 'local',
        'path' => 'private/applications/cv.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 7,
        'sha256' => hash('sha256', 'safe-cv'),
        'visibility' => 'restricted',
        'scan_status' => 'clean',
        'processing_status' => 'ready',
    ]);
    $file = ApplicationFile::query()->create([
        'application_id' => $application->id,
        'media_asset_id' => $media->id,
        'classification' => 'cv',
    ]);
    $url = URL::temporarySignedRoute('application-files.download', now()->addMinutes(5), ['file' => $file]);

    $this->get($url)->assertRedirect('/login');
    $this->actingAs($officer)->get($url)->assertOk();

    $this->assertDatabaseHas('audit_events', [
        'action' => 'private_file.downloaded',
        'auditable_type' => Application::class,
        'auditable_id' => $application->id,
        'actor_id' => $officer->id,
    ]);
});
