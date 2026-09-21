<?php

declare(strict_types=1);

namespace App\Actions\PageComposition;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Enums\PageCompositionState;
use App\Models\PageComposition;
use App\Models\PageSection;
use App\Models\PageSectionAction;
use App\Models\PageSectionMedia;
use App\Models\PageSectionRelation;
use App\Models\PageSectionVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreatePageCompositionDraftAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(
        User $actor,
        PageComposition $source,
        string $correlationId,
    ): PageComposition {
        abort_unless($actor->hasPermission('pages.create'), 403);

        return DB::transaction(function () use ($actor, $source, $correlationId): PageComposition {
            $locked = PageComposition::query()
                ->with([
                    'sections.currentVersion.sectionRelations',
                    'sections.currentVersion.media',
                    'sections.currentVersion.actions',
                ])
                ->lockForUpdate()
                ->findOrFail($source->getKey());
            $existing = PageComposition::query()
                ->where('page_key', $locked->page_key)
                ->where('locale', $locked->locale)
                ->whereIn('state', [PageCompositionState::Draft, PageCompositionState::ChangesRequested])
                ->latest('version_no')
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            $versionNo = ((int) PageComposition::query()
                ->where('page_key', $locked->page_key)
                ->where('locale', $locked->locale)
                ->max('version_no')) + 1;
            $draft = PageComposition::query()->create([
                'content_item_id' => $locked->content_item_id,
                'template_id' => $locked->template_id,
                'based_on_id' => $locked->getKey(),
                'page_key' => $locked->page_key,
                'locale' => $locked->locale,
                'template_type' => $locked->template_type,
                'state' => PageCompositionState::Draft,
                'version_no' => $versionNo,
                'lock_version' => 1,
                'content_hash' => hash('sha256', $locked->content_hash.'|draft|'.$versionNo),
                'created_by' => $actor->getKey(),
            ]);

            foreach ($locked->sections as $sourceSection) {
                $sourceVersion = $sourceSection->currentVersion;
                if ($sourceVersion === null) {
                    continue;
                }
                $section = PageSection::query()->create([
                    'page_composition_id' => $draft->getKey(),
                    'stable_key' => $sourceSection->stable_key,
                    'editor_label' => $sourceSection->editor_label,
                    'sort_order' => $sourceSection->sort_order,
                    'required' => $sourceSection->required,
                    'locked' => $sourceSection->locked,
                ]);
                $version = PageSectionVersion::query()->create([
                    'page_section_id' => $section->getKey(),
                    'version_no' => 1,
                    'locale' => $sourceVersion->locale,
                    'type' => $sourceVersion->type,
                    'variant' => $sourceVersion->variant,
                    'content' => $sourceVersion->content,
                    'presentation' => $sourceVersion->presentation,
                    'enabled' => $sourceVersion->enabled,
                    'visibility_rule' => $sourceVersion->visibility_rule,
                    'visible_from' => $sourceVersion->visible_from,
                    'visible_until' => $sourceVersion->visible_until,
                    'maximum_items' => $sourceVersion->maximum_items,
                    'selection_mode' => $sourceVersion->selection_mode,
                    'content_hash' => $sourceVersion->content_hash,
                    'created_by' => $actor->getKey(),
                ]);
                $section->forceFill(['current_version_id' => $version->getKey()])->save();

                foreach ($sourceVersion->sectionRelations as $relation) {
                    PageSectionRelation::query()->create([
                        'page_section_version_id' => $version->getKey(),
                        'relation_type' => $relation->relation_type,
                        'related_type' => $relation->related_type,
                        'related_id' => $relation->related_id,
                        'sort_order' => $relation->sort_order,
                        'metadata' => $relation->metadata,
                    ]);
                }
                foreach ($sourceVersion->media as $media) {
                    PageSectionMedia::query()->create([
                        'page_section_version_id' => $version->getKey(),
                        'media_asset_id' => $media->media_asset_id,
                        'role' => $media->role,
                        'sort_order' => $media->sort_order,
                        'focal_x' => $media->focal_x,
                        'focal_y' => $media->focal_y,
                        'decorative' => $media->decorative,
                        'caption' => $media->caption,
                    ]);
                }
                foreach ($sourceVersion->actions as $action) {
                    PageSectionAction::query()->create([
                        'page_section_version_id' => $version->getKey(),
                        'label' => $action->label,
                        'action_type' => $action->action_type,
                        'internal_route' => $action->internal_route,
                        'external_url' => $action->external_url,
                        'destination_type' => $action->destination_type,
                        'destination_id' => $action->destination_id,
                        'button_variant' => $action->button_variant,
                        'accessible_description' => $action->accessible_description,
                        'open_new_context' => $action->open_new_context,
                        'sort_order' => $action->sort_order,
                    ]);
                }
            }

            $this->audit->record(new AuditData(
                action: 'page_composition.draft_created',
                auditableType: PageComposition::class,
                auditableId: (string) $draft->getKey(),
                actorId: (string) $actor->getKey(),
                correlationId: $correlationId,
                afterHash: $draft->content_hash,
                metadata: ['based_on_id' => $locked->getKey(), 'version_no' => $versionNo],
            ));

            return $draft->refresh();
        }, attempts: 3);
    }
}
