<?php

declare(strict_types=1);

namespace App\Support\Settings;

final readonly class HomepageHeroSettings
{
    private const SLIDE_COUNT = 3;

    private const DEFAULT_IMAGE = '/images/impact-intelligence-hero-v1.webp';

    public function __construct(private EffectiveSettings $settings) {}

    /**
     * @return array{
     *     slides: list<array{
     *         slot: int,
     *         order: int,
     *         eyebrow: string,
     *         heading: string,
     *         summary: string,
     *         image: string
     *     }>,
     *     autoplay: bool,
     *     interval_ms: int,
     *     pause_on_hover: bool,
     *     primary_action: array{label: string, href: string}|null,
     *     secondary_action: array{label: string, href: string}|null
     * }
     */
    public function viewData(string $locale): array
    {
        $locale = $locale === 'am' ? 'am' : 'en';
        $slides = [];

        for ($slot = 1; $slot <= self::SLIDE_COUNT; $slot++) {
            $prefix = "homepage.hero.slide_{$slot}";

            if (! $this->settings->boolean("{$prefix}.enabled")) {
                continue;
            }

            $slides[] = [
                'slot' => $slot,
                'order' => $this->settings->integer("{$prefix}.order"),
                'eyebrow' => $this->settings->string("{$prefix}.eyebrow_{$locale}"),
                'heading' => $this->settings->string("{$prefix}.heading_{$locale}"),
                'summary' => $this->settings->string("{$prefix}.summary_{$locale}"),
                'image' => $this->settings->mediaReference("{$prefix}.image") ?? asset(self::DEFAULT_IMAGE),
            ];
        }

        usort(
            $slides,
            static fn (array $left, array $right): int => [$left['order'], $left['slot']] <=> [$right['order'], $right['slot']],
        );

        return [
            'slides' => $slides,
            'autoplay' => $this->settings->boolean('homepage.hero.autoplay'),
            'interval_ms' => $this->settings->integer('homepage.hero.interval_seconds') * 1000,
            'pause_on_hover' => $this->settings->boolean('homepage.hero.pause_on_hover'),
            'primary_action' => $this->action(
                $this->settings->string('homepage.hero.primary_destination'),
                $locale,
            ),
            'secondary_action' => $this->action(
                $this->settings->string('homepage.hero.secondary_destination'),
                $locale,
            ),
        ];
    }

    /** @return array{label: string, href: string}|null */
    private function action(string $destination, string $locale): ?array
    {
        if ($destination === 'none') {
            return null;
        }

        return match ($destination) {
            'consultation' => [
                'label' => __('Request a consultation'),
                'href' => route('consultation.create', ['locale' => $locale]),
            ],
            'services' => [
                'label' => __('Services'),
                'href' => route('services.index', ['locale' => $locale]),
            ],
            'case_studies' => [
                'label' => __('Examine our work'),
                'href' => route('case-studies.index', ['locale' => $locale]),
            ],
            'industries' => [
                'label' => __('Industries'),
                'href' => route('industries.index', ['locale' => $locale]),
            ],
            'experts' => [
                'label' => __('Experts'),
                'href' => route('experts.index', ['locale' => $locale]),
            ],
            'insights' => [
                'label' => __('Insights'),
                'href' => route('insights.index', ['locale' => $locale]),
            ],
            'contact' => [
                'label' => __('Contact'),
                'href' => route('contact.create', ['locale' => $locale]),
            ],
            default => null,
        };
    }
}
