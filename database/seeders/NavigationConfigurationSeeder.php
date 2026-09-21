<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PageNavigationConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

final class NavigationConfigurationSeeder extends Seeder
{
    /** @var array<string, string> */
    private const AMHARIC_LABELS = [
        'Home' => 'መነሻ',
        'About' => 'ስለ እኛ',
        'Services' => 'አገልግሎቶች',
        'Industries' => 'ዘርፎች',
        'Experts' => 'ባለሙያዎች',
        'Case studies' => 'የሥራ ልምዶች',
        'Insights' => 'ግንዛቤዎች',
        'Events' => 'ዝግጅቶች',
        'Careers' => 'የሥራ ዕድሎች',
        'Request a consultation' => 'የማማከር ጥያቄ',
        'Submit an RFP' => 'የፕሮፖዛል ጥያቄ',
        'Contact' => 'ያግኙን',
        'Search' => 'ፍለጋ',
        'Privacy notice' => 'የግላዊነት ማስታወቂያ',
        'Cookie notice' => 'የኩኪ ማስታወቂያ',
        'Terms of use' => 'የአጠቃቀም ውሎች',
        'Accessibility statement' => 'የተደራሽነት መግለጫ',
    ];

    /** @var array<string, list<array{string, string, string}>> */
    private const LOCATIONS = [
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

    public function run(): void
    {
        $actor = User::query()->oldest()->first();
        foreach (['en', 'am'] as $locale) {
            foreach (self::LOCATIONS as $location => $items) {
                foreach ($items as $index => [$label, $route, $icon]) {
                    $localizedLabel = $locale === 'am'
                        ? self::AMHARIC_LABELS[$label]
                        : $label;
                    $configuration = PageNavigationConfiguration::query()->firstOrCreate(
                        ['location' => $location, 'locale' => $locale, 'route_name' => $route],
                        [
                            'label' => $localizedLabel,
                            'icon' => $icon,
                            'sort_order' => $index + 1,
                            'enabled' => true,
                            'updated_by' => $actor?->getKey(),
                            'published_at' => now('UTC'),
                        ],
                    );
                    if ($locale === 'am' && $configuration->label === $label) {
                        $configuration->forceFill(['label' => $localizedLabel])->save();
                    }
                }

                Cache::forget("public-navigation:{$location}:{$locale}");
            }
        }
    }
}
