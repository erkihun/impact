<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Content\UpdateContentData;
use App\Enums\ContentWorkflowState;
use App\Exceptions\ContentVersionConflictException;
use App\Models\ContentItem;
use App\Models\ContentVersion;
use App\Models\User;
use App\Models\WorkflowEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class UpdateContentAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(User $actor, UpdateContentData $data): ContentItem
    {
        return DB::transaction(function () use ($actor, $data): ContentItem {
            $content = ContentItem::query()->lockForUpdate()->findOrFail($data->contentItemId);
            Gate::forUser($actor)->authorize('update', $content);
            $currentVersionId = (string) $content->current_version_id;
            if ($currentVersionId !== $data->expectedCurrentVersionId) {
                throw new ContentVersionConflictException(
                    $data->expectedCurrentVersionId,
                    $currentVersionId,
                );
            }

            $current = ContentVersion::query()
                ->whereBelongsTo($content)
                ->lockForUpdate()
                ->findOrFail($currentVersionId);
            $from = ContentWorkflowState::from((string) $content->getRawOriginal('status'));
            $nextVersionNo = (int) ContentVersion::query()
                ->where('content_item_id', $content->getKey())
                ->where('locale', $current->locale)
                ->orderByDesc('version_no')
                ->value('version_no') + 1;
            $payload = [
                'content_item_id' => $content->getKey(),
                'locale' => $current->locale,
                'version_no' => $nextVersionNo,
                'slug' => $current->slug,
                'title' => $data->title,
                'summary' => $data->summary,
                'body' => ['content' => $data->body],
                'workflow_state' => ContentWorkflowState::Draft,
                'created_by' => $actor->getKey(),
            ];
            $version = ContentVersion::query()->create([
                ...$payload,
                'content_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            ]);
            $beforeHash = hash('sha256', $content->toJson());
            $content->update([
                'current_version_id' => $version->getKey(),
                'status' => ContentWorkflowState::Draft,
            ]);
            WorkflowEvent::query()->create([
                'content_item_id' => $content->getKey(),
                'content_version_id' => $version->getKey(),
                'from_state' => $from,
                'to_state' => ContentWorkflowState::Draft,
                'actor_id' => $actor->getKey(),
                'note' => 'content_revision_created',
                'correlation_id' => $data->correlationId,
            ]);
            $this->audit->record(new AuditData(
                action: 'content.revision_created',
                auditableType: ContentItem::class,
                auditableId: (string) $content->getKey(),
                actorId: (string) $actor->getKey(),
                correlationId: $data->correlationId,
                beforeHash: $beforeHash,
                afterHash: hash('sha256', $content->fresh()->toJson()),
                metadata: [
                    'previous_version_id' => $currentVersionId,
                    'version_id' => $version->getKey(),
                    'version_no' => $nextVersionNo,
                ],
            ));

            return $content->refresh();
        }, attempts: 3);
    }
}
