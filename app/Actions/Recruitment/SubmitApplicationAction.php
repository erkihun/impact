<?php

declare(strict_types=1);

namespace App\Actions\Recruitment;

use App\Contracts\AuditRecorder;
use App\Contracts\Clock;
use App\Contracts\PublicReferenceGenerator;
use App\Data\Audit\AuditData;
use App\Data\Recruitment\SubmitApplicationData;
use App\Enums\ApplicationStatus;
use App\Enums\ConsentCategory;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\ApplicationStatusHistory;
use App\Models\ConsentRecord;
use App\Models\MediaAsset;
use App\Models\Vacancy;
use App\Notifications\Recruitment\ApplicationReceivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class SubmitApplicationAction
{
    public function __construct(
        private PublicReferenceGenerator $references,
        private AuditRecorder $audit,
        private Clock $clock,
    ) {}

    public function execute(SubmitApplicationData $data): Application
    {
        try {
            return DB::transaction(function () use ($data): Application {
                $vacancy = Vacancy::query()->lockForUpdate()->findOrFail($data->vacancyId);
                if (! $vacancy->acceptsApplications()) {
                    throw ValidationException::withMessages([
                        'vacancy' => __('This opportunity is closed or no longer accepting applications.'),
                    ]);
                }

                $media = MediaAsset::query()
                    ->whereKey($data->mediaAssetId)
                    ->where('scan_status', 'quarantined')
                    ->lockForUpdate()
                    ->firstOrFail();

                $application = Application::query()->create([
                    'vacancy_id' => $vacancy->id,
                    'reference_no' => $this->references->generate('application'),
                    'applicant_name' => $data->applicantName,
                    'email' => mb_strtolower($data->email),
                    'phone_encrypted' => $data->phone,
                    'cover_letter_encrypted' => $data->coverLetter,
                    'status' => ApplicationStatus::Received,
                    'retention_until' => $this->clock->today()->addDays(
                        (int) config('impact.retention.application_days', 730),
                    ),
                    'submitted_at' => $this->clock->now(),
                ]);

                ApplicationFile::query()->create([
                    'application_id' => $application->id,
                    'media_asset_id' => $media->id,
                    'classification' => 'cv',
                ]);
                ApplicationStatusHistory::query()->create([
                    'application_id' => $application->id,
                    'from_status' => null,
                    'to_status' => ApplicationStatus::Received,
                    'actor_id' => null,
                    'reason' => 'public_submission',
                    'correlation_id' => $data->correlationId,
                ]);
                ConsentRecord::query()->create([
                    'subject_type' => 'application',
                    'subject_key_hash' => hash('sha256', (string) $application->id),
                    'category' => ConsentCategory::Necessary,
                    'decision' => true,
                    'policy_version' => $data->policyVersion,
                    'source' => 'public.application',
                    'ip_hash' => $data->ipHash,
                    'recorded_at' => $this->clock->now(),
                ]);
                $this->audit->record(new AuditData(
                    action: 'application.received',
                    auditableType: Application::class,
                    auditableId: (string) $application->id,
                    actorId: null,
                    correlationId: $data->correlationId,
                    afterHash: hash('sha256', $application->toJson()),
                    metadata: ['vacancy_id' => $vacancy->id, 'media_asset_id' => $media->id],
                    ipHash: $data->ipHash,
                ));
                DB::afterCommit(static fn () => Notification::route('mail', $application->email)
                    ->notify((new ApplicationReceivedNotification(
                        $application->applicant_name,
                        $application->reference_no,
                        $vacancy->title,
                    ))->locale($vacancy->locale)));

                return $application;
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->removeOrphanedUpload($data->mediaAssetId);

            throw $exception;
        }
    }

    private function removeOrphanedUpload(string $mediaAssetId): void
    {
        if (ApplicationFile::query()->where('media_asset_id', $mediaAssetId)->exists()) {
            return;
        }

        $media = MediaAsset::query()->find($mediaAssetId);
        if ($media === null) {
            return;
        }

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }
}
