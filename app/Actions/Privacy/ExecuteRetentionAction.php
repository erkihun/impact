<?php

declare(strict_types=1);

namespace App\Actions\Privacy;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Privacy\ExecuteRetentionData;
use App\Enums\ApplicationStatus;
use App\Enums\MediaStatus;
use App\Enums\RetentionRunMode;
use App\Enums\RetentionRunStatus;
use App\Enums\SubmissionStatus;
use App\Jobs\Media\DeleteMediaObjectsJob;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\EngagementSubmission;
use App\Models\EngagementSubmissionHistory;
use App\Models\EventRegistration;
use App\Models\LegalHold;
use App\Models\MediaAsset;
use App\Models\RetentionRun;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ExecuteRetentionAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(ExecuteRetentionData $data): RetentionRun
    {
        $approver = $this->resolveApprover($data);
        $applications = Application::query()
            ->whereNull('retention_processed_at')
            ->whereDate('retention_until', '<=', today('UTC'))
            ->orderBy('retention_until')
            ->limit($data->limit)
            ->get();
        $submissions = EngagementSubmission::query()
            ->whereNull('retention_processed_at')
            ->whereDate('retention_until', '<=', today('UTC'))
            ->orderBy('retention_until')
            ->limit($data->limit)
            ->get();
        $registrations = EventRegistration::query()
            ->whereNull('retention_processed_at')
            ->whereNotNull('retention_until')
            ->whereDate('retention_until', '<=', today('UTC'))
            ->orderBy('retention_until')
            ->limit($data->limit)
            ->get();
        $candidates = $applications->concat($submissions)->concat($registrations);
        $heldKeys = $candidates
            ->filter(fn (Model $record): bool => $this->hasActiveLegalHold($record))
            ->map(fn (Model $record): string => $record::class.':'.$record->getKey())
            ->all();

        $run = RetentionRun::query()->create([
            'policy_version' => $data->policyVersion,
            'mode' => $data->mode,
            'status' => RetentionRunStatus::Running,
            'candidate_count' => $candidates->count(),
            'legal_hold_count' => count($heldKeys),
            'approved_by' => $approver?->getKey(),
            'correlation_id' => $data->correlationId,
            'started_at' => now('UTC'),
        ]);

        if ($data->mode === RetentionRunMode::DryRun) {
            $run->update([
                'status' => RetentionRunStatus::Completed,
                'completed_at' => now('UTC'),
            ]);
            $this->recordRunAudit($run, $data, $approver);

            return $run->refresh();
        }

        $processed = 0;
        $failures = [];
        foreach ($candidates as $candidate) {
            $candidateKey = $candidate::class.':'.$candidate->getKey();
            if (in_array($candidateKey, $heldKeys, true)) {
                continue;
            }

            try {
                if ($candidate instanceof Application) {
                    $this->anonymizeApplication($candidate, $data, $approver);
                } elseif ($candidate instanceof EngagementSubmission) {
                    $this->anonymizeSubmission($candidate, $data, $approver);
                } elseif ($candidate instanceof EventRegistration) {
                    $this->anonymizeEventRegistration($candidate, $data, $approver);
                }
                $processed++;
            } catch (Throwable $exception) {
                report($exception);
                $failures[] = [
                    'record_type' => $candidate::class,
                    'record_id' => (string) $candidate->getKey(),
                    'error' => $exception::class,
                ];
            }
        }

        $run->update([
            'status' => $failures === []
                ? RetentionRunStatus::Completed
                : RetentionRunStatus::CompletedWithErrors,
            'processed_count' => $processed,
            'failure_count' => count($failures),
            'failures' => $failures === [] ? null : $failures,
            'completed_at' => now('UTC'),
        ]);
        $this->recordRunAudit($run, $data, $approver);

        return $run->refresh();
    }

    private function resolveApprover(ExecuteRetentionData $data): ?User
    {
        if ($data->mode === RetentionRunMode::DryRun) {
            return null;
        }

        $approver = $data->approvedBy === null
            ? null
            : User::query()->find($data->approvedBy);
        if ($approver === null || ! $approver->hasPermission('privacy.retention.execute')) {
            throw new AuthorizationException('An active authorized retention approver is required.');
        }

        return $approver;
    }

    private function hasActiveLegalHold(Model $record): bool
    {
        return LegalHold::query()
            ->where('holdable_type', $record::class)
            ->where('holdable_id', $record->getKey())
            ->whereNull('released_at')
            ->exists();
    }

    private function anonymizeApplication(
        Application $candidate,
        ExecuteRetentionData $data,
        User $approver,
    ): void {
        DB::transaction(function () use ($candidate, $data, $approver): void {
            $application = Application::query()
                ->with('files.mediaAsset')
                ->lockForUpdate()
                ->findOrFail($candidate->getKey());
            if ($application->retention_processed_at !== null || $this->hasActiveLegalHold($application)) {
                return;
            }

            $beforeHash = hash('sha256', $application->toJson());
            $from = ApplicationStatus::from((string) $application->getRawOriginal('status'));
            $mediaAssetIds = $this->revokeMedia($application->files->pluck('mediaAsset')->filter()->all());
            $application->files()->delete();
            $application->update([
                'status' => ApplicationStatus::Anonymized,
                'applicant_name' => 'Anonymized',
                'email' => hash('sha256', $application->email).'@anonymized.invalid',
                'phone_encrypted' => null,
                'cover_letter_encrypted' => null,
                'retention_processed_at' => now('UTC'),
            ]);
            ApplicationStatusHistory::query()->create([
                'application_id' => $application->getKey(),
                'from_status' => $from,
                'to_status' => ApplicationStatus::Anonymized,
                'actor_id' => $approver->getKey(),
                'reason' => 'Approved retention policy execution.',
                'correlation_id' => $data->correlationId,
            ]);
            $this->recordSubjectAudit($application, $beforeHash, $data, $approver);
            $this->dispatchMediaDeletion($mediaAssetIds, $approver, $data);
        }, attempts: 3);
    }

    private function anonymizeSubmission(
        EngagementSubmission $candidate,
        ExecuteRetentionData $data,
        User $approver,
    ): void {
        DB::transaction(function () use ($candidate, $data, $approver): void {
            $submission = EngagementSubmission::query()
                ->with('files.mediaAsset')
                ->lockForUpdate()
                ->findOrFail($candidate->getKey());
            if ($submission->retention_processed_at !== null || $this->hasActiveLegalHold($submission)) {
                return;
            }

            $beforeHash = hash('sha256', $submission->toJson());
            $from = SubmissionStatus::from((string) $submission->getRawOriginal('status'));
            $mediaAssetIds = $this->revokeMedia($submission->files->pluck('mediaAsset')->filter()->all());
            $submission->files()->delete();
            $submission->update([
                'status' => SubmissionStatus::Closed,
                'contact_name' => 'Anonymized',
                'organization_name' => null,
                'role' => null,
                'email' => hash('sha256', $submission->email).'@anonymized.invalid',
                'phone_encrypted' => null,
                'description_encrypted' => 'Anonymized',
                'timeframe' => null,
                'budget_range' => null,
                'assigned_to' => null,
                'retention_processed_at' => now('UTC'),
            ]);
            EngagementSubmissionHistory::query()->create([
                'engagement_submission_id' => $submission->getKey(),
                'from_status' => $from,
                'to_status' => SubmissionStatus::Closed,
                'actor_id' => $approver->getKey(),
                'assigned_to' => null,
                'note' => 'Approved retention policy execution.',
                'correlation_id' => $data->correlationId,
            ]);
            $this->recordSubjectAudit($submission, $beforeHash, $data, $approver);
            $this->dispatchMediaDeletion($mediaAssetIds, $approver, $data);
        }, attempts: 3);
    }

    private function anonymizeEventRegistration(
        EventRegistration $candidate,
        ExecuteRetentionData $data,
        User $approver,
    ): void {
        DB::transaction(function () use ($candidate, $data, $approver): void {
            $registration = EventRegistration::query()
                ->lockForUpdate()
                ->findOrFail($candidate->getKey());
            if ($registration->retention_processed_at !== null || $this->hasActiveLegalHold($registration)) {
                return;
            }

            $beforeHash = hash('sha256', $registration->toJson());
            $anonymousEmail = hash('sha256', $registration->normalized_email).'@anonymized.invalid';
            $registration->update([
                'name' => 'Anonymized',
                'email' => $anonymousEmail,
                'normalized_email' => $anonymousEmail,
                'status' => 'anonymized',
                'confirmation_token_hash' => null,
                'retention_processed_at' => now('UTC'),
            ]);
            $this->recordSubjectAudit($registration, $beforeHash, $data, $approver);
        }, attempts: 3);
    }

    /**
     * @param  array<int, MediaAsset>  $assets
     * @return list<string>
     */
    private function revokeMedia(array $assets): array
    {
        $ids = [];
        foreach ($assets as $asset) {
            $asset->forceFill([
                'original_name' => 'anonymized',
                'title' => null,
                'alt_text' => null,
                'credit' => null,
                'copyright' => null,
                'scan_status' => MediaStatus::Deleted,
                'processing_status' => MediaStatus::Deleted,
            ])->save();
            $ids[] = (string) $asset->getKey();
        }

        return $ids;
    }

    /** @param list<string> $mediaAssetIds */
    private function dispatchMediaDeletion(
        array $mediaAssetIds,
        User $approver,
        ExecuteRetentionData $data,
    ): void {
        if ($mediaAssetIds !== []) {
            DB::afterCommit(static fn () => DeleteMediaObjectsJob::dispatch(
                $mediaAssetIds,
                (string) $approver->getKey(),
                $data->correlationId,
            ));
        }
    }

    private function recordSubjectAudit(
        Model $record,
        string $beforeHash,
        ExecuteRetentionData $data,
        User $approver,
    ): void {
        $this->audit->record(new AuditData(
            action: 'retention.record_anonymized',
            auditableType: $record::class,
            auditableId: (string) $record->getKey(),
            actorId: (string) $approver->getKey(),
            correlationId: $data->correlationId,
            beforeHash: $beforeHash,
            afterHash: hash('sha256', $record->fresh()->toJson()),
            metadata: ['policy_version' => $data->policyVersion],
        ));
    }

    private function recordRunAudit(
        RetentionRun $run,
        ExecuteRetentionData $data,
        ?User $approver,
    ): void {
        $this->audit->record(new AuditData(
            action: 'retention.run_completed',
            auditableType: RetentionRun::class,
            auditableId: (string) $run->getKey(),
            actorId: $approver === null ? null : (string) $approver->getKey(),
            correlationId: $data->correlationId,
            afterHash: hash('sha256', $run->fresh()->toJson()),
            metadata: [
                'mode' => $data->mode->value,
                'policy_version' => $data->policyVersion,
                'candidate_count' => $run->candidate_count,
                'processed_count' => $run->processed_count,
                'legal_hold_count' => $run->legal_hold_count,
                'failure_count' => $run->failure_count,
            ],
        ));
    }
}
