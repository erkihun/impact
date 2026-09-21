<?php

declare(strict_types=1);

namespace App\Actions\Engagement;

use App\Actions\Media\QuarantineUploadAction;
use App\Contracts\AuditRecorder;
use App\Contracts\Clock;
use App\Contracts\PublicReferenceGenerator;
use App\Data\Audit\AuditData;
use App\Data\Engagement\CreateEngagementSubmissionData;
use App\Data\Media\QuarantineUploadData;
use App\Enums\ConsentCategory;
use App\Enums\SubmissionStatus;
use App\Models\ConsentRecord;
use App\Models\EngagementSubmission;
use App\Models\EngagementSubmissionHistory;
use App\Models\MediaAsset;
use App\Models\Service;
use App\Models\SubmissionFile;
use App\Notifications\Engagement\EngagementReceivedNotification;
use App\Support\Settings\EffectiveSettings;
use App\Support\Settings\EngagementSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class CreateEngagementSubmissionAction
{
    public function __construct(
        private PublicReferenceGenerator $references,
        private AuditRecorder $audit,
        private Clock $clock,
        private QuarantineUploadAction $uploads,
        private EngagementSettings $engagement,
        private EffectiveSettings $settings,
    ) {}

    public function execute(CreateEngagementSubmissionData $data): EngagementSubmission
    {
        if ($data->idempotencyKey !== null) {
            $existing = EngagementSubmission::query()
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        /** @var list<MediaAsset> $mediaAssets */
        $mediaAssets = [];
        $created = false;

        try {
            foreach ($data->attachments as $index => $attachment) {
                $mediaAssets[] = $this->uploads->execute(new QuarantineUploadData(
                    file: $attachment,
                    visibility: 'restricted',
                    allowedMimeTypes: config('impact.files.engagement_allowed_mime_types', []),
                    locale: $data->locale,
                    retentionUntil: $this->clock->today()
                        ->addDays((int) config('impact.retention.engagement_days', 730))
                        ->toDateString(),
                    correlationId: $data->correlationId,
                    fieldName: "attachments.{$index}",
                ));
            }

            $submission = DB::transaction(function () use ($data, $mediaAssets, &$created): EngagementSubmission {
                if ($data->idempotencyKey !== null) {
                    $existing = EngagementSubmission::query()
                        ->where('idempotency_key', $data->idempotencyKey)
                        ->lockForUpdate()
                        ->first();

                    if ($existing !== null) {
                        return $existing;
                    }
                }

                if ($data->serviceId !== null && ! Service::query()
                    ->whereKey($data->serviceId)
                    ->where('status', 'published')
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'service_id' => __('The selected service is not available.'),
                    ]);
                }

                $status = $mediaAssets === [] ? SubmissionStatus::Received : SubmissionStatus::Scanning;
                $submission = EngagementSubmission::query()->create([
                    'reference_no' => $this->references->generate(
                        $this->engagement->referencePrefix().'-'.$data->type->value,
                    ),
                    'idempotency_key' => $data->idempotencyKey,
                    'type' => $data->type,
                    'status' => $status,
                    'locale' => $data->locale,
                    'contact_name' => $data->contactName,
                    'organization_name' => $data->organizationName,
                    'role' => $data->role,
                    'email' => mb_strtolower($data->email),
                    'phone_encrypted' => $data->phone,
                    'service_id' => $data->serviceId,
                    'industry_id' => $data->industryId,
                    'description_encrypted' => $data->description,
                    'timeframe' => $data->timeframe,
                    'budget_range' => $data->budgetRange,
                    'retention_until' => $this->clock->today()
                        ->addDays($this->settings->integer('privacy.data_retention_days')),
                    'submitted_at' => $this->clock->now(),
                ]);

                foreach ($mediaAssets as $mediaAsset) {
                    SubmissionFile::query()->create([
                        'submission_id' => $submission->id,
                        'media_asset_id' => $mediaAsset->id,
                        'classification' => 'supporting_document',
                    ]);
                }

                EngagementSubmissionHistory::query()->create([
                    'engagement_submission_id' => $submission->id,
                    'from_status' => null,
                    'to_status' => $status,
                    'actor_id' => null,
                    'assigned_to' => null,
                    'note' => 'public_submission',
                    'correlation_id' => $data->correlationId,
                ]);
                ConsentRecord::query()->create([
                    'subject_type' => 'engagement_submission',
                    'subject_key_hash' => hash('sha256', (string) $submission->getKey()),
                    'category' => ConsentCategory::Necessary,
                    'decision' => true,
                    'policy_version' => $data->policyVersion,
                    'source' => "public.{$data->type->value}",
                    'ip_hash' => $data->ipHash,
                    'recorded_at' => $this->clock->now(),
                ]);

                $this->audit->record(new AuditData(
                    action: 'engagement.received',
                    auditableType: EngagementSubmission::class,
                    auditableId: (string) $submission->getKey(),
                    actorId: null,
                    correlationId: $data->correlationId,
                    afterHash: hash('sha256', $submission->toJson()),
                    metadata: [
                        'type' => $data->type->value,
                        'locale' => $data->locale,
                        'attachment_count' => count($mediaAssets),
                    ],
                    ipHash: $data->ipHash,
                ));
                if ($this->engagement->acknowledgementEnabled()) {
                    DB::afterCommit(static fn () => Notification::route('mail', $submission->email)
                        ->notify((new EngagementReceivedNotification(
                            $submission->contact_name,
                            $submission->reference_no,
                        ))->locale($submission->locale)));
                }
                $created = true;

                return $submission;
            }, attempts: 3);

            if (! $created) {
                $this->removeOrphanedUploads($mediaAssets);
            }

            return $submission;
        } catch (Throwable $exception) {
            $this->removeOrphanedUploads($mediaAssets);

            throw $exception;
        }
    }

    /** @param list<MediaAsset> $mediaAssets */
    private function removeOrphanedUploads(array $mediaAssets): void
    {
        foreach ($mediaAssets as $mediaAsset) {
            if (SubmissionFile::query()->where('media_asset_id', $mediaAsset->id)->exists()) {
                continue;
            }

            try {
                Storage::disk($mediaAsset->disk)->delete($mediaAsset->path);
                $mediaAsset->delete();
            } catch (Throwable $cleanupFailure) {
                report($cleanupFailure);
            }
        }
    }
}
