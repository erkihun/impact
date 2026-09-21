<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Content\RollbackContentData;
use App\Enums\ContentWorkflowState;
use App\Exceptions\ContentVersionConflictException;
use App\Models\ContentItem;
use App\Models\ContentVersion;
use App\Models\User;
use App\Models\WorkflowEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class RollbackContentAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(User $actor, RollbackContentData $data): ContentItem
    {
        return DB::transaction(function () use ($actor, $data): ContentItem {
            $content = ContentItem::query()->lockForUpdate()->findOrFail($data->contentItemId);
            Gate::forUser($actor)->authorize('rollback', $content);
            $currentVersionId = (string) $content->current_version_id;
            if ($currentVersionId !== $data->expectedCurrentVersionId) {
                throw new ContentVersionConflictException(
                    $data->expectedCurrentVersionId,
                    $currentVersionId,
                );
            }
            if ($data->sourceVersionId === $currentVersionId) {
                throw ValidationException::withMessages([
                    'source_version_id' => __('Select a historical version to restore.'),
                ]);
            }

            $source = ContentVersion::query()
                ->whereBelongsTo($content)
                ->lockForUpdate()
                ->findOrFail($data->sourceVersionId);
            $wasApproved = WorkflowEvent::query()
                ->where('content_item_id', $content->getKey())
                ->where('content_version_id', $source->getKey())
                ->whereIn('to_state', [
                    ContentWorkflowState::Approved->value,
                    ContentWorkflowState::Published->value,
                ])
                ->exists();
            if (! $wasApproved) {
                throw ValidationException::withMessages([
                    'source_version_id' => __('Only a previously approved version can be restored.'),
                ]);
            }

            $from = ContentWorkflowState::from((string) $content->getRawOriginal('status'));
            $nextVersionNo = (int) ContentVersion::query()
                ->where('content_item_id', $content->getKey())
                ->where('locale', $source->locale)
                ->orderByDesc('version_no')
                ->value('version_no') + 1;
            $payload = [
                'content_item_id' => $content->getKey(),
                'locale' => $source->locale,
                'version_no' => $nextVersionNo,
                'slug' => $source->slug,
                'title' => $source->title,
                'summary' => $source->summary,
                'body' => $source->body,
                'workflow_state' => ContentWorkflowState::Draft,
                'created_by' => $actor->getKey(),
            ];
            $restored = ContentVersion::query()->create([
                ...$payload,
                'content_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            ]);
            $beforeHash = hash('sha256', $content->toJson());
            $content->update([
                'current_version_id' => $restored->getKey(),
                'status' => ContentWorkflowState::Draft,
            ]);
            WorkflowEvent::query()->create([
                'content_item_id' => $content->getKey(),
                'content_version_id' => $restored->getKey(),
                'from_state' => $from,
                'to_state' => ContentWorkflowState::Draft,
                'actor_id' => $actor->getKey(),
                'note' => $data->reason,
                'correlation_id' => $data->correlationId,
            ]);
            $this->audit->record(new AuditData(
                action: 'content.rollback_revision_created',
                auditableType: ContentItem::class,
                auditableId: (string) $content->getKey(),
                actorId: (string) $actor->getKey(),
                correlationId: $data->correlationId,
                beforeHash: $beforeHash,
                afterHash: hash('sha256', $content->fresh()->toJson()),
                metadata: [
                    'source_version_id' => $source->getKey(),
                    'restored_version_id' => $restored->getKey(),
                    'version_no' => $nextVersionNo,
                    'reason' => $data->reason,
                ],
            ));

            return $content->refresh();
        }, attempts: 3);
    }
}
