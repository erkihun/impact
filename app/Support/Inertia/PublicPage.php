<?php

declare(strict_types=1);

namespace App\Support\Inertia;

use App\Services\PageComposer;
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
     */
    public static function render(
        string $component,
        ?string $pageKey,
        array $props,
        ?string $headingOverride = null,
        ?string $summaryOverride = null,
    ): Response {
        $pageKey ??= self::pageKeyForCurrentRoute();
        $composition = $pageKey === null
            ? null
            : app(PageComposer::class)->published($pageKey, app()->getLocale());

        return Inertia::render($component, [
            ...$props,
            'composition' => CompositionPresenter::present($composition, $headingOverride, $summaryOverride),
        ]);
    }

    /**
     * Home first, then the given trail; a null href marks the current page.
     *
     * @param  array<string, string|null>  $trail
     * @return array<int, array{label: string, href: string|null}>
     */
    public static function breadcrumbs(array $trail): array
    {
        $items = [__('Home') => route('localized-home', ['locale' => app()->getLocale()]), ...$trail];

        return collect($items)
            ->map(fn (?string $href, string $label): array => ['label' => $label, 'href' => $href])
            ->values()
            ->all();
    }

    public static function pageKeyForCurrentRoute(): ?string
    {
        $routeName = request()->route()?->getName();

        return match ($routeName) {
            'home', 'localized-home' => 'home',
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
