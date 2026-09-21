<?php

declare(strict_types=1);

use App\Actions\Media\ApproveMediaAction;
use App\Actions\Media\QuarantineUploadAction;
use App\Data\Media\ApproveMediaData;
use App\Data\Media\QuarantineUploadData;
use App\Enums\MediaStatus;
use App\Jobs\Media\ProcessMediaAssetJob;
use App\Models\MediaAsset;
use App\Models\Role;
use App\Models\User;
use App\Services\Media\ImageVariantGenerator;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    Storage::fake('local');
    Storage::fake('public');
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

it('creates responsive image variants and promotes only clean processed media', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->whereIn('code', ['editor', 'publisher'])->get());
    $asset = app(QuarantineUploadAction::class)->execute(new QuarantineUploadData(
        file: UploadedFile::fake()->image('evidence.jpg', 1800, 1200),
        visibility: 'public',
        allowedMimeTypes: ['image/jpeg'],
        uploadedBy: $editor->id,
        correlationId: (string) str()->uuid(),
    ));
    $asset->update([
        'scan_status' => MediaStatus::Clean,
        'processing_status' => MediaStatus::Processing,
    ]);

    $job = new ProcessMediaAssetJob($asset->id, (string) str()->uuid());
    $job->handle(app(ImageVariantGenerator::class));
    expect($asset->refresh()->processing_status)->toBe(MediaStatus::Ready)
        ->and($asset->variants()->count())->toBe(3);
    $quarantinePath = $asset->path;

    app(ApproveMediaAction::class)->execute($editor, new ApproveMediaData(
        mediaAssetId: $asset->id,
        actorId: $editor->id,
        correlationId: (string) str()->uuid(),
    ));

    expect($asset->refresh()->disk)->toBe('public')
        ->and($asset->path)->toStartWith('media/')
        ->and($asset->path)->not->toContain('evidence')
        ->and(Storage::disk('public')->exists($asset->path))->toBeTrue()
        ->and(Storage::disk('local')->exists($quarantinePath))->toBeFalse();
    $this->assertDatabaseHas('audit_events', ['action' => 'media.approved']);
});

it('refuses promotion before scan and processing are complete', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->whereIn('code', ['editor', 'publisher'])->get());
    $asset = MediaAsset::query()->create([
        'original_name' => 'report.pdf',
        'disk' => 'local',
        'path' => 'quarantine/report.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 10,
        'sha256' => hash('sha256', 'not-clean'),
        'visibility' => 'private',
        'scan_status' => 'quarantined',
        'processing_status' => 'quarantined',
    ]);

    expect(fn () => app(ApproveMediaAction::class)->execute($editor, new ApproveMediaData(
        mediaAssetId: $asset->id,
        actorId: $editor->id,
        correlationId: (string) str()->uuid(),
    )))->toThrow(ValidationException::class);
});

it('serves approved private files only through an authorized signed route', function (): void {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('code', 'editor')->sole());
    Storage::disk('local')->put('media/private/report.pdf', '%PDF-safe');
    $asset = MediaAsset::query()->create([
        'original_name' => 'board-report.pdf',
        'disk' => 'local',
        'path' => 'media/private/report.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 9,
        'sha256' => hash('sha256', '%PDF-safe'),
        'visibility' => 'private',
        'scan_status' => 'clean',
        'processing_status' => 'ready',
    ]);
    $url = URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['media' => $asset]);

    $this->actingAs($editor)->get($url)->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=board-report.pdf');
    $this->actingAs($editor)->get(route('media.download', $asset))->assertForbidden();
});
