<?php

declare(strict_types=1);

use App\Models\EngagementSubmission;
use App\Models\MediaAsset;
use App\Models\Role;
use App\Models\SubmissionFile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    Storage::fake('local');
});

it('renders localized public RFP and contact intake pages', function (): void {
    $this->get('/en/request-for-proposal')
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="type" value="rfp"', false);
    $this->get('/am/request-for-proposal')
        ->assertOk()
        ->assertSee('የፕሮጀክት መግለጫዎን በደህንነት ያጋሩ።');
    $this->get('/en/contact')
        ->assertOk()
        ->assertSee('value="partnership"', false)
        ->assertSee('value="media"', false);
});

it('quarantines an RFP attachment under a random object key and appends initial history', function (): void {
    $this->post('/en/rfp-requests', [
        'type' => 'rfp',
        'contact_name' => 'Aster Bekele',
        'organization_name' => 'Example Institution',
        'email' => 'aster@example.com',
        'description' => 'We request a proposal for a multi-year institutional transformation program.',
        'attachments' => [
            UploadedFile::fake()->create('confidential-procurement.pdf', 100, 'application/pdf'),
        ],
        'privacy_acknowledged' => '1',
        'policy_version' => '2026-07-26',
    ])->assertRedirect()->assertSessionHas('submission.reference');

    $submission = EngagementSubmission::query()->sole();
    $media = MediaAsset::query()->sole();
    expect($submission->status->value)->toBe('scanning')
        ->and($media->path)->not->toContain('confidential-procurement')
        ->and($media->visibility->value)->toBe('restricted');
    Storage::disk('local')->assertExists($media->path);
    $this->assertDatabaseHas('submission_files', [
        'submission_id' => $submission->id,
        'media_asset_id' => $media->id,
    ]);
    $this->assertDatabaseHas('engagement_submission_histories', [
        'engagement_submission_id' => $submission->id,
        'from_status' => null,
        'to_status' => 'scanning',
        'actor_id' => null,
    ]);
});

it('allows only an authorized engagement reviewer to use a signed clean-file download', function (): void {
    $submission = EngagementSubmission::query()->create([
        'reference_no' => 'RFP-SECURE-001',
        'type' => 'rfp',
        'status' => 'triaged',
        'locale' => 'en',
        'contact_name' => 'Example Contact',
        'email' => 'contact@example.com',
        'description_encrypted' => 'A confidential request for proposal.',
        'retention_until' => now()->addYear(),
        'submitted_at' => now(),
    ]);
    $media = MediaAsset::query()->create([
        'original_name' => 'proposal.pdf',
        'disk' => 'local',
        'path' => 'private/rfp/proposal.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 128,
        'sha256' => hash('sha256', 'proposal'),
        'visibility' => 'restricted',
        'scan_status' => 'clean',
        'processing_status' => 'ready',
    ]);
    Storage::disk('local')->put($media->path, 'proposal');
    $file = SubmissionFile::query()->create([
        'submission_id' => $submission->id,
        'media_asset_id' => $media->id,
        'classification' => 'supporting_document',
    ]);
    $reviewer = User::factory()->create();
    $reviewer->roles()->attach(Role::query()->where('code', 'engagement_officer')->sole());
    $mediaEditor = User::factory()->create();
    $mediaEditor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    $url = URL::temporarySignedRoute(
        'submission-files.download',
        now()->addMinutes(5),
        ['file' => $file],
    );

    $this->actingAs($mediaEditor)->withSession(privilegedSession($mediaEditor))
        ->get($url)
        ->assertForbidden();
    $this->actingAs($mediaEditor)->withSession(privilegedSession($mediaEditor))
        ->get(URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['media' => $media]))
        ->assertForbidden();
    $this->actingAs($reviewer)->withSession(privilegedSession($reviewer))
        ->get($url)
        ->assertOk()
        ->assertHeader('Content-Disposition');

    $this->assertDatabaseHas('audit_events', [
        'action' => 'private_file.downloaded',
        'auditable_type' => EngagementSubmission::class,
        'auditable_id' => $submission->id,
        'actor_id' => $reviewer->id,
    ]);
});
