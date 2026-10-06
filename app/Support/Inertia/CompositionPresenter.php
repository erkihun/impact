<?php

declare(strict_types=1);

namespace App\Support\Inertia;

use App\Data\PageComposition\CompositionViewData;
use App\Data\PageComposition\SectionViewData;
use App\Services\Seo\MediaImagePresenter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Turns a published (or previewed) page composition into plain arrays for the
 * React section renderer. Only public, renderable values cross the boundary.
 */
final class CompositionPresenter
{
    /** @return array<string, mixed>|null */
    public static function present(
        ?CompositionViewData $composition,
        ?string $headingOverride = null,
        ?string $summaryOverride = null,
    ): ?array {
        if ($composition === null) {
            return null;
        }

        return [
            'id' => $composition->id,
            'pageKey' => $composition->pageKey,
            'preview' => $composition->preview,
            'sections' => collect($composition->sections)
                ->map(fn (SectionViewData $section): array => self::section($section, $headingOverride, $summaryOverride))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private static function section(SectionViewData $section, ?string $headingOverride, ?string $summaryOverride): array
    {
        $content = $section->content;

        if ($section->type->value === 'page_header') {
            $content['heading'] = $headingOverride ?? ($content['heading'] ?? null);
            $content['summary'] = $summaryOverride ?? ($content['summary'] ?? null);
        }

        return [
            'id' => $section->id,
            'key' => $section->stableKey,
            'type' => $section->type->value,
            'variant' => $section->variant,
            'surface' => $section->presentation['surface_tone'] ?? 'white',
            'width' => $section->presentation['container_width'] ?? 'standard',
            'spacing' => $section->presentation['spacing_top'] ?? 'standard',
            'content' => $content,
            'actions' => collect($section->actions)->map(fn ($action): ?array => self::action($action))->filter()->values()->all(),
            'media' => self::media($section->media),
            'relations' => collect($section->relations)->map(fn ($relation): array => [
                'type' => Str::headline((string) $relation->relation_type),
                'title' => $relation->related->currentVersion->title ?? __('Published item'),
            ])->values()->all(),
        ];
    }

    /** @return array<string, mixed>|null */
    private static function action(object $action): ?array
    {
        $href = $action->external_url;

        if ($action->internal_route && Route::has($action->internal_route)) {
            $href = route($action->internal_route);
        }

        if (! $href) {
            return null;
        }

        return [
            'label' => $action->label,
            'href' => $href,
            'primary' => $action->button_variant->value === 'primary',
            'newContext' => (bool) $action->open_new_context,
            'description' => $action->accessible_description,
        ];
    }

    /**
     * @param  array<int, object>  $media
     * @return array<string, mixed>|null
     */
    private static function media(array $media): ?array
    {
        $usage = collect($media)->first();

        if (! $usage?->asset) {
            return null;
        }

        // Optimized WebP derivatives with intrinsic size, never the original upload.
        $image = app(MediaImagePresenter::class)->attributes($usage->asset, '(min-width: 1024px) 50vw, 100vw', 'md');

        return [
            'src' => $image['src'] ?? $usage->asset->publicUrl(),
            'srcset' => $image['srcset'] ?? null,
            'sizes' => $image['sizes'] ?? null,
            'width' => $image['width'] ?? null,
            'height' => $image['height'] ?? null,
            'alt' => $usage->decorative ? '' : ($usage->asset->alt_text ?? ''),
            'decorative' => (bool) $usage->decorative,
            'caption' => $usage->caption,
        ];
    }
}
