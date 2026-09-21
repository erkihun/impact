<?php

declare(strict_types=1);

namespace App\Actions\Engagement;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Engagement\UpdateSubmissionData;
use App\Enums\SubmissionStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\EngagementSubmission;
use App\Models\EngagementSubmissionHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateSubmissionAction
{
    /** @var array<string, list<SubmissionStatus>> */
    private const TRANSITIONS = [
        'received' => [SubmissionStatus::Scanning, SubmissionStatus::Triaged, SubmissionStatus::Rejected, SubmissionStatus::Quarantined],
        'scanning' => [SubmissionStatus::Triaged, SubmissionStatus::Rejected, SubmissionStatus::Quarantined],
        'triaged' => [SubmissionStatus::Assigned, SubmissionStatus::NotProceeding, SubmissionStatus::Rejected],
        'assigned' => [SubmissionStatus::InProgress],
        'in_progress' => [SubmissionStatus::AwaitingClient, SubmissionStatus::Contacted, SubmissionStatus::Qualified, SubmissionStatus::Resolved, SubmissionStatus::NotProceeding],
        'awaiting_client' => [SubmissionStatus::InProgress, SubmissionStatus::Contacted, SubmissionStatus::NotProceeding],
        'contacted' => [SubmissionStatus::Qualified, SubmissionStatus::Resolved, SubmissionStatus::NotProceeding],
        'qualified' => [SubmissionStatus::Resolved, SubmissionStatus::Closed],
        'resolved' => [SubmissionStatus::Closed],
        'not_proceeding' => [SubmissionStatus::Closed],
        'rejected' => [SubmissionStatus::Closed],
        'quarantined' => [SubmissionStatus::Scanning, SubmissionStatus::Rejected],
        'closed' => [],
    ];

    public function __construct(private AuditRecorder $audit) {}

    /** @return list<SubmissionStatus> */
    public function allowedDestinations(SubmissionStatus $from): array
    {
        return self::TRANSITIONS[$from->value];
    }

    /**
     * @throws AuthorizationException
     */
    public function execute(User $actor, UpdateSubmissionData $data): EngagementSubmission
    {
        if (! $actor->hasPermission('engagement.update-status')) {
            throw new AuthorizationException;
        }
        if ($data->assignedTo !== null && ! $actor->hasPermission('engagement.assign')) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($data): EngagementSubmission {
            $submission = EngagementSubmission::query()
                ->lockForUpdate()
                ->findOrFail($data->submissionId);
            $from = SubmissionStatus::from((string) $submission->getRawOriginal('status'));
            if (! in_array($data->status, $this->allowedDestinations($from), true)) {
                throw new InvalidStateTransitionException($from, $data->status);
            }
            if ($data->status === SubmissionStatus::Assigned && $data->assignedTo === null) {
                throw ValidationException::withMessages([
                    'assigned_to' => __('An assignee is required for the assigned state.'),
                ]);
            }
            if ($data->assignedTo !== null && ! User::query()
                ->whereKey($data->assignedTo)
                ->where('status', 'active')
                ->exists()) {
                throw ValidationException::withMessages([
                    'assigned_to' => __('The selected assignee is unavailable.'),
                ]);
            }

            $beforeHash = hash('sha256', $submission->toJson());
            $submission->update([
                'status' => $data->status,
                'assigned_to' => $data->assignedTo ?? $submission->assigned_to,
            ]);
            EngagementSubmissionHistory::query()->create([
                'engagement_submission_id' => $submission->id,
                'from_status' => $from,
                'to_status' => $data->status,
                'actor_id' => $data->actorId,
                'assigned_to' => $submission->assigned_to,
                'note' => $data->note,
                'correlation_id' => $data->correlationId,
            ]);
            $this->audit->record(new AuditData(
                action: 'engagement.status_updated',
                auditableType: EngagementSubmission::class,
                auditableId: (string) $submission->id,
                actorId: $data->actorId,
                correlationId: $data->correlationId,
                beforeHash: $beforeHash,
                afterHash: hash('sha256', $submission->fresh()->toJson()),
                metadata: ['from' => $from->value, 'to' => $data->status->value],
            ));

            return $submission->refresh();
        }, attempts: 3);
    }
}
