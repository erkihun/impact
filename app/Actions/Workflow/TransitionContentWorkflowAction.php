<?php

declare(strict_types=1);

namespace App\Actions\Workflow;

use App\Contracts\AuditRecorder;
use App\Contracts\Clock;
use App\Data\Audit\AuditData;
use App\Data\Workflow\TransitionContentData;
use App\Enums\ContentWorkflowState;
use App\Jobs\Search\SyncContentSearchDocumentJob;
use App\Models\ContentItem;
use App\Models\ContentVersion;
use App\Models\PublicationSchedule;
use App\Models\User;
use App\Models\WorkflowEvent;
use App\Services\Workflow\ContentWorkflow;
use App\Support\Settings\EffectiveSettings;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class TransitionContentWorkflowAction
{
    public function __construct(
        private ContentWorkflow $workflow,
        private AuditRecorder $audit,
        private Clock $clock,
        private EffectiveSettings $settings,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function execute(
        User $actor,
        ContentItem $content,
        TransitionContentData $data,
    ): ContentItem {
        Gate::forUser($actor)->authorize('transition', [$content, $data->to]);

        return DB::transaction(function () use ($actor, $content, $data): ContentItem {
            /** @var ContentItem $lockedContent */
            $lockedContent = ContentItem::query()->lockForUpdate()->findOrFail($content->getKey());
            /** @var ContentVersion $version */
            $version = ContentVersion::query()
                ->whereBelongsTo($lockedContent)
                ->lockForUpdate()
                ->findOrFail($data->contentVersionId);

            $from = ContentWorkflowState::from(
                (string) $lockedContent->getRawOriginal('status'),
            );
            $this->workflow->assertCanTransition($from, $data->to);
            if ($data->to === ContentWorkflowState::Scheduled && $data->publishAt === null) {
                throw ValidationException::withMessages([
                    'publish_at' => __('A future publication time is required.'),
                ]);
            }
            if (in_array($data->to, [
                ContentWorkflowState::Scheduled,
                ContentWorkflowState::Published,
            ], true)
                && $this->settings->boolean('localization.require_default_before_publication')
                && ! ContentVersion::query()
                    ->where('content_item_id', $lockedContent->id)
                    ->where('locale', $this->settings->string('localization.default_locale'))
                    ->exists()) {
                throw ValidationException::withMessages([
                    'to' => __('A default-language version is required before publication.'),
                ]);
            }

            $beforeHash = hash('sha256', $lockedContent->toJson());
            $lockedContent->forceFill([
                'status' => $data->to,
                'current_version_id' => $version->getKey(),
                'published_at' => $data->to === ContentWorkflowState::Published
                    ? $this->clock->now()
                    : $lockedContent->published_at,
            ])->save();

            $version->forceFill(['workflow_state' => $data->to])->save();

            if ($from === ContentWorkflowState::Scheduled) {
                PublicationSchedule::query()
                    ->where('content_item_id', $lockedContent->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => $data->to === ContentWorkflowState::Published
                            ? 'completed'
                            : 'cancelled',
                    ]);
            }
            if ($data->to === ContentWorkflowState::Scheduled) {
                PublicationSchedule::query()
                    ->where('content_item_id', $lockedContent->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
                PublicationSchedule::query()->create([
                    'content_item_id' => $lockedContent->id,
                    'content_version_id' => $version->id,
                    'publish_at' => CarbonImmutable::parse(
                        $data->publishAt,
                        (string) config('app.timezone', 'UTC'),
                    )->utc(),
                    'unpublish_at' => $data->unpublishAt === null
                        ? null
                        : CarbonImmutable::parse(
                            $data->unpublishAt,
                            (string) config('app.timezone', 'UTC'),
                        )->utc(),
                    'status' => 'pending',
                    'created_by' => $actor->id,
                ]);
            }

            WorkflowEvent::query()->create([
                'content_item_id' => $lockedContent->getKey(),
                'content_version_id' => $version->getKey(),
                'from_state' => $from->value,
                'to_state' => $data->to,
                'actor_id' => $actor->getKey(),
                'note' => $data->note,
                'correlation_id' => $data->correlationId,
            ]);

            $this->audit->record(new AuditData(
                action: "content.transition.{$data->to->value}",
                auditableType: ContentItem::class,
                auditableId: (string) $lockedContent->getKey(),
                actorId: (string) $actor->getKey(),
                correlationId: $data->correlationId,
                beforeHash: $beforeHash,
                afterHash: hash('sha256', $lockedContent->fresh()->toJson()),
                metadata: [
                    'from' => $from->value,
                    'to' => $data->to->value,
                    'version_id' => $version->getKey(),
                    'reason_recorded' => filled($data->note),
                    'publish_at' => $data->publishAt,
                    'unpublish_at' => $data->unpublishAt,
                ],
            ));

            DB::afterCommit(static fn () => SyncContentSearchDocumentJob::dispatch($lockedContent->id));

            return $lockedContent->refresh();
        }, attempts: 3);
    }
}
