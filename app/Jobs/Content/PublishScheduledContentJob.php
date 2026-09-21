<?php

declare(strict_types=1);

namespace App\Jobs\Content;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Enums\ContentWorkflowState;
use App\Jobs\Search\SyncContentSearchDocumentJob;
use App\Models\ContentItem;
use App\Models\ContentVersion;
use App\Models\PublicationSchedule;
use App\Models\WorkflowEvent;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PublishScheduledContentJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public function __construct(public readonly string $scheduleId)
    {
        $this->onQueue('publishing');
    }

    public function uniqueId(): string
    {
        return $this->scheduleId;
    }

    public function handle(AuditRecorder $audit): void
    {
        DB::transaction(function () use ($audit): void {
            $schedule = PublicationSchedule::query()->lockForUpdate()->findOrFail($this->scheduleId);
            if ($schedule->status === 'published') {
                $this->unpublishIfDue($schedule, $audit);

                return;
            }

            $publishAt = Carbon::parse((string) $schedule->getRawOriginal('publish_at'));
            if ($schedule->status !== 'pending' || $publishAt->isFuture()) {
                return;
            }

            $content = ContentItem::query()->lockForUpdate()->findOrFail($schedule->content_item_id);
            $version = ContentVersion::query()->lockForUpdate()->findOrFail($schedule->content_version_id);

            $workflowState = (string) $version->getRawOriginal('workflow_state');
            if (! in_array($workflowState, [
                ContentWorkflowState::Approved->value,
                ContentWorkflowState::Scheduled->value,
            ], true)) {
                $schedule->update(['status' => 'cancelled']);

                return;
            }

            $from = ContentWorkflowState::from($workflowState);
            $version->update(['workflow_state' => ContentWorkflowState::Published]);
            $content->update([
                'current_version_id' => $version->id,
                'status' => ContentWorkflowState::Published,
                'published_at' => now('UTC'),
            ]);
            $schedule->update([
                'status' => $schedule->unpublish_at === null ? 'completed' : 'published',
            ]);
            $correlationId = (string) Str::uuid7();
            WorkflowEvent::query()->create([
                'content_item_id' => $content->id,
                'content_version_id' => $version->id,
                'from_state' => $from,
                'to_state' => ContentWorkflowState::Published,
                'actor_id' => $schedule->created_by,
                'note' => 'scheduled_publication',
                'correlation_id' => $correlationId,
            ]);

            $audit->record(new AuditData(
                action: 'content.published_by_schedule',
                auditableType: ContentItem::class,
                auditableId: (string) $content->id,
                actorId: (string) $schedule->created_by,
                correlationId: $correlationId,
                afterHash: hash('sha256', $version->content_hash),
                metadata: ['schedule_id' => $schedule->id, 'version_id' => $version->id],
                ipHash: null,
            ));
            DB::afterCommit(static fn () => SyncContentSearchDocumentJob::dispatch($content->id));
        }, attempts: 3);
    }

    private function unpublishIfDue(PublicationSchedule $schedule, AuditRecorder $audit): void
    {
        $unpublishAt = $schedule->getRawOriginal('unpublish_at');
        if ($unpublishAt === null || Carbon::parse((string) $unpublishAt)->isFuture()) {
            return;
        }

        $content = ContentItem::query()->lockForUpdate()->findOrFail($schedule->content_item_id);
        $version = ContentVersion::query()->lockForUpdate()->findOrFail($schedule->content_version_id);
        if ((string) $content->current_version_id !== (string) $version->id
            || $content->getRawOriginal('status') !== ContentWorkflowState::Published->value
            || $version->getRawOriginal('workflow_state') !== ContentWorkflowState::Published->value) {
            $schedule->update(['status' => 'cancelled']);

            return;
        }

        $version->update(['workflow_state' => ContentWorkflowState::Unpublished]);
        $content->update(['status' => ContentWorkflowState::Unpublished]);
        $schedule->update(['status' => 'completed']);
        $correlationId = (string) Str::uuid7();
        WorkflowEvent::query()->create([
            'content_item_id' => $content->id,
            'content_version_id' => $version->id,
            'from_state' => ContentWorkflowState::Published,
            'to_state' => ContentWorkflowState::Unpublished,
            'actor_id' => $schedule->created_by,
            'note' => 'scheduled_unpublication',
            'correlation_id' => $correlationId,
        ]);
        $audit->record(new AuditData(
            action: 'content.unpublished_by_schedule',
            auditableType: ContentItem::class,
            auditableId: (string) $content->id,
            actorId: (string) $schedule->created_by,
            correlationId: $correlationId,
            afterHash: hash('sha256', $version->content_hash),
            metadata: ['schedule_id' => $schedule->id, 'version_id' => $version->id],
        ));
        DB::afterCommit(static fn () => SyncContentSearchDocumentJob::dispatch($content->id));
    }
}
