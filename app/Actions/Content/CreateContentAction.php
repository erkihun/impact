<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Content\CreateContentData;
use App\Enums\ContentWorkflowState;
use App\Models\ContentItem;
use App\Models\ContentSlug;
use App\Models\ContentVersion;
use App\Models\WorkflowEvent;
use Illuminate\Support\Facades\DB;

final readonly class CreateContentAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(CreateContentData $data): ContentItem
    {
        return DB::transaction(function () use ($data): ContentItem {
            $content = ContentItem::query()->create([
                'type' => $data->type,
                'owner_id' => $data->ownerId,
                'status' => ContentWorkflowState::Draft,
            ]);
            ContentSlug::query()->create([
                'content_item_id' => $content->getKey(),
                'locale' => $data->locale,
                'slug' => $data->slug,
            ]);
            $payload = [
                'content_item_id' => $content->id,
                'locale' => $data->locale,
                'version_no' => 1,
                'slug' => $data->slug,
                'title' => $data->title,
                'summary' => $data->summary,
                'body' => $data->body,
                'workflow_state' => ContentWorkflowState::Draft,
                'created_by' => $data->ownerId,
            ];
            $version = ContentVersion::query()->create([
                ...$payload,
                'content_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            ]);
            $content->update(['current_version_id' => $version->id]);
            WorkflowEvent::query()->create([
                'content_item_id' => $content->id,
                'content_version_id' => $version->id,
                'from_state' => null,
                'to_state' => ContentWorkflowState::Draft,
                'actor_id' => $data->ownerId,
                'note' => 'content_created',
                'correlation_id' => $data->correlationId,
            ]);
            $this->audit->record(new AuditData(
                action: 'content.created',
                auditableType: ContentItem::class,
                auditableId: (string) $content->id,
                actorId: $data->ownerId,
                correlationId: $data->correlationId,
                afterHash: hash('sha256', $content->toJson()),
                metadata: ['version_id' => $version->id, 'locale' => $data->locale],
            ));

            return $content->refresh();
        }, attempts: 3);
    }
}
