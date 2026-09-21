<?php

declare(strict_types=1);

namespace App\Actions\Recruitment;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Recruitment\ChangeApplicationStatusData;
use App\Enums\ApplicationStatus;
use App\Enums\MediaStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Jobs\Media\DeleteMediaObjectsJob;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class ChangeApplicationStatusAction
{
    /** @var array<string, list<ApplicationStatus>> */
    private const TRANSITIONS = [
        'received' => [ApplicationStatus::Screening, ApplicationStatus::Rejected, ApplicationStatus::Withdrawn],
        'screening' => [ApplicationStatus::Shortlisted, ApplicationStatus::Rejected, ApplicationStatus::Withdrawn],
        'shortlisted' => [ApplicationStatus::Interview, ApplicationStatus::Rejected, ApplicationStatus::Withdrawn],
        'interview' => [ApplicationStatus::Offered, ApplicationStatus::Rejected, ApplicationStatus::Withdrawn],
        'offered' => [ApplicationStatus::Hired, ApplicationStatus::Rejected, ApplicationStatus::Withdrawn],
        'hired' => [ApplicationStatus::Anonymized],
        'rejected' => [ApplicationStatus::Anonymized],
        'withdrawn' => [ApplicationStatus::Anonymized],
        'anonymized' => [],
    ];

    public function __construct(private AuditRecorder $audit) {}

    /** @return list<ApplicationStatus> */
    public function allowedDestinations(ApplicationStatus $from): array
    {
        return self::TRANSITIONS[$from->value];
    }

    public function execute(User $actor, ChangeApplicationStatusData $data): Application
    {
        return DB::transaction(function () use ($actor, $data): Application {
            $application = Application::query()
                ->with('files.mediaAsset')
                ->lockForUpdate()
                ->findOrFail($data->applicationId);
            Gate::forUser($actor)->authorize('updateStatus', $application);
            $from = ApplicationStatus::from((string) $application->getRawOriginal('status'));
            if (! in_array($data->to, $this->allowedDestinations($from), true)) {
                throw new InvalidStateTransitionException($from, $data->to);
            }

            $beforeHash = hash('sha256', $application->toJson());
            $changes = ['status' => $data->to];
            $mediaAssetIds = [];
            if ($data->to === ApplicationStatus::Anonymized) {
                $mediaAssetIds = $application->files
                    ->pluck('media_asset_id')
                    ->filter()
                    ->values()
                    ->all();
                foreach ($application->files as $applicationFile) {
                    $applicationFile->mediaAsset?->forceFill([
                        'original_name' => 'anonymized',
                        'title' => null,
                        'alt_text' => null,
                        'credit' => null,
                        'copyright' => null,
                        'scan_status' => MediaStatus::Deleted,
                        'processing_status' => MediaStatus::Deleted,
                    ])->save();
                }
                $application->files()->delete();
                $changes += [
                    'applicant_name' => 'Anonymized',
                    'email' => hash('sha256', $application->email).'@anonymized.invalid',
                    'phone_encrypted' => null,
                    'cover_letter_encrypted' => null,
                ];
            }
            $application->update($changes);
            ApplicationStatusHistory::query()->create([
                'application_id' => $application->id,
                'from_status' => $from,
                'to_status' => $data->to,
                'actor_id' => $data->actorId,
                'reason' => $data->reason,
                'correlation_id' => $data->correlationId,
            ]);
            $this->audit->record(new AuditData(
                action: 'application.status_updated',
                auditableType: Application::class,
                auditableId: $application->id,
                actorId: $data->actorId,
                correlationId: $data->correlationId,
                beforeHash: $beforeHash,
                afterHash: hash('sha256', $application->fresh()->toJson()),
                metadata: ['from' => $from->value, 'to' => $data->to->value],
            ));
            if ($mediaAssetIds !== []) {
                DB::afterCommit(static fn () => DeleteMediaObjectsJob::dispatch(
                    $mediaAssetIds,
                    $data->actorId,
                    $data->correlationId,
                ));
            }

            return $application->refresh();
        }, attempts: 3);
    }
}
