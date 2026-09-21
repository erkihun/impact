<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PageNavigationConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

final readonly class PublicNavigation
{
    /** @var array<string, list<array{string, string, string}>> */
    private const FALLBACK_LOCATIONS = [
        'primary' => [
            ['Home', 'localized-home', 'home'],
            ['About', 'about.show', 'about'],
            ['Services', 'services.index', 'services'],
            ['Industries', 'industries.index', 'industries'],
            ['Experts', 'experts.index', 'experts'],
            ['Case studies', 'case-studies.index', 'case-studies'],
            ['Insights', 'insights.index', 'insights'],
        ],
        'footer_explore' => [
            ['Services', 'services.index', 'services'],
            ['Industries', 'industries.index', 'industries'],
            ['Experts', 'experts.index', 'experts'],
            ['Case studies', 'case-studies.index', 'case-studies'],
            ['Insights', 'insights.index', 'insights'],
            ['Events', 'events.index', 'events'],
            ['Careers', 'careers.index', 'careers'],
        ],
        'footer_engage' => [
            ['Request a consultation', 'consultation.create', 'consultation'],
            ['Submit an RFP', 'rfp.create', 'rfp'],
            ['Contact', 'contact.create', 'contact'],
            ['Search', 'search', 'search'],
        ],
        'footer_legal' => [
            ['Privacy notice', 'legal.privacy', 'privacy'],
            ['Cookie notice', 'legal.cookies', 'cookies'],
            ['Terms of use', 'legal.terms', 'terms'],
            ['Accessibility statement', 'legal.accessibility', 'accessibility'],
        ],
    ];

    /** @return Collection<int, array<string, mixed>> */
    public function location(string $location, string $locale): Collection
    {
        return Cache::remember(
            "public-navigation:{$location}:{$locale}",
            now('UTC')->addMinutes(10),
            fn (): Collection => $this->publishedOrFallback($location, $locale),
        );
    }

    /** @return Collection<int, array<string, mixed>> */
    private function publishedOrFallback(string $location, string $locale): Collection
    {
        $items = PageNavigationConfiguration::query()
            ->where('location', $location)
            ->where('locale', $locale)
            ->where('enabled', true)
            ->whereNotNull('published_at')
            ->where(fn ($query) => $query->whereNull('visible_from')->orWhere('visible_from', '<=', now('UTC')))
            ->where(fn ($query) => $query->whereNull('visible_until')->orWhere('visible_until', '>', now('UTC')))
            ->whereNull('parent_id')
            ->with(['children' => fn ($query) => $query
                ->where('enabled', true)
                ->whereNotNull('published_at')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PageNavigationConfiguration $item): array => $this->item($item, $locale));

        if ($items->isNotEmpty()) {
            return $items;
        }

        return collect(self::FALLBACK_LOCATIONS[$location] ?? [])
            ->map(fn (array $item, int $index): array => $this->fallbackItem(
                $item,
                $index,
                $location,
                $locale,
            ));
    }

    /**
     * @param  array{string, string, string}  $item
     * @return array<string, mixed>
     */
    private function fallbackItem(array $item, int $index, string $location, string $locale): array
    {
        $translatedLabel = trans($item[0], [], $locale);

        return [
            'id' => "fallback-{$location}-{$index}",
            'label' => is_string($translatedLabel) ? $translatedLabel : $item[0],
            'description' => null,
            'icon' => $item[2],
            'route' => $item[1],
            'url' => Route::has($item[1])
                ? route($item[1], ['locale' => $locale])
                : '#',
            'children' => collect(),
        ];
    }

    /** @return array<string, mixed> */
    private function item(PageNavigationConfiguration $item, string $locale): array
    {
        $parameters = $item->route_parameters ?? [];
        $parameters = ['locale' => $locale, ...$parameters];

        return [
            'id' => (string) $item->getKey(),
            'label' => $item->label,
            'description' => $item->description,
            'icon' => $item->icon,
            'route' => $item->route_name,
            'url' => Route::has($item->route_name) ? route($item->route_name, $parameters) : '#',
            'children' => $item->children
                ->map(fn (PageNavigationConfiguration $child): array => $this->item($child, $locale))
                ->values(),
        ];
    }
}
