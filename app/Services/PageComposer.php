<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\PageComposition\CompositionViewData;
use App\Data\PageComposition\SectionViewData;
use App\Enums\MediaStatus;
use App\Enums\MediaVisibility;
use App\Enums\PageCompositionState;
use App\Models\PageComposition;
use App\Models\PageSection;
use App\Support\PageSectionRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

final readonly class PageComposer
{
    public function __construct(private PageSectionRegistry $registry) {}

    public function published(string $pageKey, string $locale): ?CompositionViewData
    {
        return Cache::remember(
            "public-page:{$pageKey}:{$locale}",
            now('UTC')->addMinutes(5),
            function () use ($pageKey, $locale): ?CompositionViewData {
                $composition = PageComposition::query()
                    ->where('page_key', $pageKey)
                    ->where('locale', $locale)
                    ->where('state', PageCompositionState::Published)
                    ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now('UTC')))
                    ->where(fn ($query) => $query->whereNull('unpublish_at')->orWhere('unpublish_at', '>', now('UTC')))
                    ->latest('version_no')
                    ->first();

                return $composition === null ? null : $this->compose($composition);
            },
        );
    }

    public function preview(PageComposition $composition): CompositionViewData
    {
        return $this->compose($composition, preview: true);
    }

    private function compose(PageComposition $composition, bool $preview = false): CompositionViewData
    {
        $composition->load([
            'sections.currentVersion.sectionRelations.related',
            'sections.currentVersion.media.asset.variants',
            'sections.currentVersion.actions',
        ]);

        $now = CarbonImmutable::now('UTC');
        $sections = $composition->sections
            ->filter(function (PageSection $section) use ($now, $preview): bool {
                $version = $section->currentVersion;
                if ($version === null || (! $preview && ! $version->enabled)) {
                    return false;
                }

                return $preview
                    || (($version->visible_from === null || $version->visible_from <= $now)
                        && ($version->visible_until === null || $version->visible_until > $now));
            })
            ->map(function (PageSection $section) use ($preview): SectionViewData {
                $version = $section->currentVersion;
                if ($version === null) {
                    throw new \LogicException('A composed section requires a current immutable version.');
                }
                $definition = $this->registry->get($version->type);
                $media = $version->media
                    ->filter(fn ($usage): bool => $preview || (
                        $usage->asset->visibility === MediaVisibility::Public
                        && $usage->asset->scan_status === MediaStatus::Clean
                        && $usage->asset->processing_status === MediaStatus::Ready
                    ))
                    ->values()->all();

                return new SectionViewData(
                    id: (string) $version->getKey(),
                    stableKey: $section->stable_key,
                    type: $version->type,
                    variant: $version->variant,
                    renderer: $definition['renderer'],
                    content: $version->content,
                    presentation: $version->presentation,
                    relations: $version->sectionRelations->values()->all(),
                    media: $media,
                    actions: $version->actions->values()->all(),
                );
            })
            ->values()
            ->all();

        return new CompositionViewData(
            id: (string) $composition->getKey(),
            pageKey: $composition->page_key,
            locale: $composition->locale,
            template: $composition->template_type,
            version: $composition->version_no,
            sections: $sections,
            preview: $preview,
        );
    }
}
