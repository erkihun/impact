<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\PageComposition\UpsertSectionData;
use App\Exceptions\ContentVersionConflictException;
use App\Models\MediaAsset;
use App\Models\PageComposition;
use App\Models\PageSection;
use App\Models\PageSectionAction;
use App\Models\PageSectionMedia;
use App\Models\PageSectionRelation;
use App\Models\PageSectionVersion;
use App\Models\User;
use App\Support\PageSectionRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class PageCompositionMutator
{
    public function __construct(
        private PageSectionRegistry $registry,
        private AuditRecorder $audit,
    ) {}

    public function add(User $actor, PageComposition $composition, UpsertSectionData $data): PageSection
    {
        return DB::transaction(function () use ($actor, $composition, $data): PageSection {
            $locked = $this->editable($actor, $composition, $data->expectedLockVersion);
            $this->registry->validate($data->type, $data->variant, $data->content, $data->presentation);
            $nextOrder = ((int) PageSection::query()
                ->where('page_composition_id', $locked->getKey())
                ->withTrashed()->max('sort_order')) + 1;

            $section = PageSection::query()->create([
                'page_composition_id' => $locked->getKey(),
                'stable_key' => $data->type->value.'-'.Str::lower(Str::random(8)),
                'editor_label' => $data->editorLabel,
                'sort_order' => $nextOrder,
            ]);
            $version = $this->createVersion($actor, $section, $data, 1);
            $this->applyMediaSelection($version, $data);
            $section->forceFill(['current_version_id' => $version->getKey()])->save();
            $this->record($actor, $locked, $section, 'page_section.added', $data->correlationId);

            return $section->refresh();
        }, attempts: 3);
    }

    public function update(
        User $actor,
        PageComposition $composition,
        PageSection $section,
        UpsertSectionData $data,
    ): PageSection {
        return DB::transaction(function () use ($actor, $composition, $section, $data): PageSection {
            $locked = $this->editable($actor, $composition, $data->expectedLockVersion);
            $target = PageSection::query()
                ->where('page_composition_id', $locked->getKey())
                ->lockForUpdate()->findOrFail($section->getKey());
            $target->load([
                'currentVersion.sectionRelations',
                'currentVersion.media',
                'currentVersion.actions',
            ]);
            $previousVersion = $target->currentVersion;
            $this->registry->validate($data->type, $data->variant, $data->content, $data->presentation);
            $nextVersion = ((int) $target->versions()->max('version_no')) + 1;
            $version = $this->createVersion($actor, $target, $data, $nextVersion);
            if ($previousVersion !== null) {
                $this->cloneAssociations($previousVersion, $version, cloneMedia: ! $data->replaceMedia);
            }
            $this->applyMediaSelection($version, $data);
            $target->forceFill([
                'editor_label' => $data->editorLabel,
                'current_version_id' => $version->getKey(),
            ])->save();
            $this->record($actor, $locked, $target, 'page_section.updated', $data->correlationId);

            return $target->refresh();
        }, attempts: 3);
    }

    /** @param list<string> $sectionIds */
    public function reorder(
        User $actor,
        PageComposition $composition,
        array $sectionIds,
        int $expectedLockVersion,
        string $correlationId,
    ): PageComposition {
        return DB::transaction(function () use (
            $actor, $composition, $sectionIds, $expectedLockVersion, $correlationId,
        ): PageComposition {
            $locked = $this->editable($actor, $composition, $expectedLockVersion);
            $actualIds = PageSection::query()
                ->where('page_composition_id', $locked->getKey())
                ->orderBy('sort_order')->pluck('id')->map(strval(...))->all();
            if (array_values(array_unique($sectionIds)) !== $sectionIds
                || collect($actualIds)->sort()->values()->all() !== collect($sectionIds)->sort()->values()->all()) {
                throw ValidationException::withMessages([
                    'section_ids' => __('The order must contain every active section exactly once.'),
                ]);
            }

            foreach ($sectionIds as $index => $sectionId) {
                PageSection::query()->whereKey($sectionId)->update(['sort_order' => $index + 10000]);
            }
            foreach ($sectionIds as $index => $sectionId) {
                PageSection::query()->whereKey($sectionId)->update(['sort_order' => $index + 1]);
            }

            $this->record($actor, $locked, null, 'page_sections.reordered', $correlationId);

            return $locked->refresh();
        }, attempts: 3);
    }

    public function duplicate(
        User $actor,
        PageComposition $composition,
        PageSection $section,
        int $expectedLockVersion,
        string $correlationId,
    ): PageSection {
        $source = $section->load('currentVersion');
        $version = $source->currentVersion;
        if ($version === null) {
            throw ValidationException::withMessages([
                'section' => __('The source section has no current immutable version.'),
            ]);
        }

        $duplicate = $this->add($actor, $composition, new UpsertSectionData(
            type: $version->type,
            variant: $version->variant,
            editorLabel: $source->editor_label.' '.__('(copy)'),
            content: $version->content,
            presentation: $version->presentation,
            enabled: $version->enabled,
            visibilityRule: $version->visibility_rule,
            visibleFrom: $version->visible_from?->toAtomString(),
            visibleUntil: $version->visible_until?->toAtomString(),
            selectionMode: $version->selection_mode,
            maximumItems: $version->maximum_items,
            expectedLockVersion: $expectedLockVersion,
            correlationId: $correlationId,
        ));
        $version->load(['sectionRelations', 'media', 'actions']);
        $duplicate->load('currentVersion');
        if ($duplicate->currentVersion !== null) {
            $this->cloneAssociations($version, $duplicate->currentVersion);
        }

        return $duplicate;
    }

    public function remove(
        User $actor,
        PageComposition $composition,
        PageSection $section,
        int $expectedLockVersion,
        string $correlationId,
    ): void {
        DB::transaction(function () use ($actor, $composition, $section, $expectedLockVersion, $correlationId): void {
            $locked = $this->editable($actor, $composition, $expectedLockVersion);
            $target = PageSection::query()
                ->where('page_composition_id', $locked->getKey())
                ->lockForUpdate()->findOrFail($section->getKey());
            if ($target->required || $target->locked) {
                throw ValidationException::withMessages([
                    'section' => __('Required or locked sections cannot be removed.'),
                ]);
            }
            $target->delete();
            $this->record($actor, $locked, $target, 'page_section.removed', $correlationId);
        }, attempts: 3);
    }

    public function restore(
        User $actor,
        PageComposition $composition,
        string $sectionId,
        int $expectedLockVersion,
        string $correlationId,
    ): PageSection {
        return DB::transaction(function () use (
            $actor, $composition, $sectionId, $expectedLockVersion, $correlationId,
        ): PageSection {
            $locked = $this->editable($actor, $composition, $expectedLockVersion);
            $section = PageSection::onlyTrashed()
                ->where('page_composition_id', $locked->getKey())
                ->lockForUpdate()->findOrFail($sectionId);
            $section->restore();
            $this->record($actor, $locked, $section, 'page_section.restored', $correlationId);

            return $section->refresh();
        }, attempts: 3);
    }

    private function editable(User $actor, PageComposition $composition, int $expected): PageComposition
    {
        Gate::forUser($actor)->authorize('update', $composition);
        $locked = PageComposition::query()->lockForUpdate()->findOrFail($composition->getKey());
        if ($locked->lock_version !== $expected) {
            throw new ContentVersionConflictException((string) $expected, (string) $locked->lock_version);
        }
        if (! $locked->isEditable()) {
            throw ValidationException::withMessages([
                'composition' => __('Only draft or changes-requested compositions can be edited.'),
            ]);
        }

        return $locked;
    }

    private function createVersion(
        User $actor,
        PageSection $section,
        UpsertSectionData $data,
        int $version,
    ): PageSectionVersion {
        $payload = [
            'page_section_id' => $section->getKey(),
            'version_no' => $version,
            'locale' => $section->composition->locale,
            'type' => $data->type,
            'variant' => $data->variant,
            'content' => $data->content,
            'presentation' => $data->presentation,
            'enabled' => $data->enabled,
            'visibility_rule' => $data->visibilityRule,
            'visible_from' => $data->visibleFrom,
            'visible_until' => $data->visibleUntil,
            'selection_mode' => $data->selectionMode,
            'maximum_items' => $data->maximumItems,
            'created_by' => $actor->getKey(),
        ];

        return PageSectionVersion::query()->create([
            ...$payload,
            'content_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        ]);
    }

    private function touch(PageComposition $composition): void
    {
        $composition->forceFill([
            'lock_version' => $composition->lock_version + 1,
            'content_hash' => hash('sha256', $composition->id.'|'.microtime(true)),
        ])->save();
    }

    private function applyMediaSelection(PageSectionVersion $version, UpsertSectionData $data): void
    {
        if (! $data->replaceMedia) {
            return;
        }
        $definition = $this->registry->get($data->type);
        if ($data->mediaAssetIds !== [] && $definition['media'] === []) {
            throw ValidationException::withMessages([
                'media_asset_ids' => __('This section type does not accept media.'),
            ]);
        }

        $assets = MediaAsset::query()->whereKey($data->mediaAssetIds)->get();
        if ($assets->count() !== count($data->mediaAssetIds)
            || $assets->contains(fn (MediaAsset $asset): bool => ! $asset->isPubliclyUsable())) {
            throw ValidationException::withMessages([
                'media_asset_ids' => __('Choose only public, clean and processed media assets.'),
            ]);
        }
        foreach ($assets->values() as $index => $asset) {
            PageSectionMedia::query()->create([
                'page_section_version_id' => $version->getKey(),
                'media_asset_id' => $asset->getKey(),
                'role' => $index === 0 ? 'primary' : 'supporting',
                'sort_order' => $index,
                'decorative' => false,
            ]);
        }
    }

    private function cloneAssociations(
        PageSectionVersion $source,
        PageSectionVersion $target,
        bool $cloneMedia = true,
    ): void {
        foreach ($source->sectionRelations as $relation) {
            PageSectionRelation::query()->create([
                'page_section_version_id' => $target->getKey(),
                'relation_type' => $relation->relation_type,
                'related_type' => $relation->related_type,
                'related_id' => $relation->related_id,
                'sort_order' => $relation->sort_order,
                'metadata' => $relation->metadata,
            ]);
        }
        if ($cloneMedia) {
            foreach ($source->media as $media) {
                PageSectionMedia::query()->create([
                    'page_section_version_id' => $target->getKey(),
                    'media_asset_id' => $media->media_asset_id,
                    'role' => $media->role,
                    'sort_order' => $media->sort_order,
                    'focal_x' => $media->focal_x,
                    'focal_y' => $media->focal_y,
                    'decorative' => $media->decorative,
                    'caption' => $media->caption,
                ]);
            }
        }
        foreach ($source->actions as $action) {
            PageSectionAction::query()->create([
                'page_section_version_id' => $target->getKey(),
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

    private function record(
        User $actor,
        PageComposition $composition,
        ?PageSection $section,
        string $action,
        string $correlationId,
    ): void {
        $this->touch($composition);
        $this->audit->record(new AuditData(
            action: $action,
            auditableType: PageComposition::class,
            auditableId: (string) $composition->getKey(),
            actorId: (string) $actor->getKey(),
            correlationId: $correlationId,
            afterHash: $composition->content_hash,
            metadata: [
                'section_id' => $section?->getKey(),
                'locale' => $composition->locale,
                'composition_version' => $composition->version_no,
            ],
        ));
    }
}
