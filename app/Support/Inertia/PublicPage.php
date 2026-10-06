<?php

declare(strict_types=1);

namespace App\Support\Inertia;

use App\Data\PageComposition\CompositionViewData;
use App\Data\PageComposition\SectionViewData;
use App\Enums\Seo\RobotsDirective;
use App\Services\PageComposer;
use App\Services\Seo\SeoHeadRenderer;
use App\Services\Seo\SeoMetadataBuilder;
use App\Services\Seo\StructuredDataBuilder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders a public React page with the managed composition the editors
 * published for it, mirroring the page keys the Blade view composer used.
 */
final class PublicPage
{
    /**
     * @param  array<string, mixed>  $props
     * @param  string|null  $pageKey  null derives the key from the current route
     * @param  list<string>  $extraHead  additional escaped head elements, e.g. an LCP image preload
     */
    public static function render(
        string $component,
        ?string $pageKey,
        array $props,
        ?string $headingOverride = null,
        ?string $summaryOverride = null,
        ?SeoMetadataBuilder $seo = null,
        array $extraHead = [],
    ): Response {
        $pageKey ??= self::pageKeyForCurrentRoute();
        $composition = $pageKey === null
            ? null
            : app(PageComposer::class)->published($pageKey, app()->getLocale());

        $seo ??= SeoMetadataBuilder::make()
            ->title($headingOverride ?? data_get($props, 'header.title'))
            ->description($summaryOverride, data_get($props, 'header.summary'));
        $seo->breadcrumbs($props['breadcrumbs'] ?? []);
        self::addVisibleFaq($seo, $composition);
        $metadata = $seo->build();

        return Inertia::render($component, [
            ...$props,
            'composition' => CompositionPresenter::present($composition, $headingOverride, $summaryOverride),
            'seo' => [
                ...$metadata->toArray(),
                'head' => [
                    ...app(SeoHeadRenderer::class)->render($metadata, includeVerification: request()->routeIs('home')),
                    ...$extraHead,
                ],
            ],
        ]);
    }

    /**
     * Metadata for a static public page with editor overrides keyed by the
     * page key. Query state other than tracking parameters makes the
     * response noindex, follow.
     */
    public static function seo(string $pageKey, string $title, ?string $description, string $schemaType = 'WebPage'): SeoMetadataBuilder
    {
        return SeoMetadataBuilder::make()
            ->subject('page', $pageKey)
            ->title($title)
            ->description($description)
            ->type('website', $schemaType)
            ->meaningfulQuery([])
            ->robots(PublicContent::hasTransientQuery() ? RobotsDirective::NoindexFollow : RobotsDirective::IndexFollow)
            ->listable();
    }

    /** FAQPage markup only for question/answer pairs the visitor can see. */
    private static function addVisibleFaq(SeoMetadataBuilder $seo, ?CompositionViewData $composition): void
    {
        if ($composition === null) {
            return;
        }

        $items = collect($composition->sections)
            ->filter(static fn (SectionViewData $section): bool => $section->type->value === 'faq')
            ->flatMap(static fn (SectionViewData $section): array => is_array($section->content['items'] ?? null) ? $section->content['items'] : [])
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(static fn (array $item): array => ['question' => (string) ($item['question'] ?? ''), 'answer' => (string) ($item['answer'] ?? '')])
            ->values()
            ->all();

        if ($items !== []) {
            $seo->structuredData(app(StructuredDataBuilder::class)->faqPage($seo->canonicalUrl(), $items));
        }
    }

    /**
     * Home first, then the given trail; a null href marks the current page.
     *
     * @param  array<string, string|null>  $trail
     * @return array<int, array{label: string, href: string|null}>
     */
    public static function breadcrumbs(array $trail): array
    {
        $items = [__('Home') => route('home'), ...$trail];

        return collect($items)
            ->map(fn (?string $href, string $label): array => ['label' => $label, 'href' => $href])
            ->values()
            ->all();
    }

    public static function pageKeyForCurrentRoute(): ?string
    {
        $routeName = request()->route()?->getName();

        return match ($routeName) {
            'home' => 'home',
            'about.show' => 'about',
            'consultation.create' => 'consultation',
            'rfp.create' => 'rfp',
            'contact.create' => 'contact',
            'legal.privacy' => 'legal.privacy',
            'legal.terms' => 'legal.terms',
            'legal.cookies' => 'legal.cookies',
            'legal.accessibility' => 'legal.accessibility',
            default => is_string($routeName) && (
                str_ends_with($routeName, '.index')
                || str_ends_with($routeName, '.show')
                || $routeName === 'search'
            ) ? $routeName : null,
        };
    }
}
