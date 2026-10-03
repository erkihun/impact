<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentSelectionMode;
use App\Enums\PageCompositionState;
use App\Enums\PageSectionType;
use App\Enums\PageTemplateType;
use App\Enums\VisibilityRule;
use App\Models\PageComposition;
use App\Models\PageSection;
use App\Models\PageSectionVersion;
use App\Models\PageTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PageCompositionSeeder extends Seeder
{
    /** @var array<string, array{PageTemplateType, string, string, string, string}> */
    private const PAGES = [
        'home' => [PageTemplateType::Homepage, 'Evidence for the decisions that shape institutions.', 'We turn complex questions into focused strategy, stronger delivery and lasting capability.'],
        'about' => [PageTemplateType::Institutional, 'Independent thinking, rooted in context.', 'Strategy, sector expertise and implementation discipline for sustained results.'],
        'services.index' => [PageTemplateType::Directory, 'Consulting services', 'Capabilities organized around client challenges and measurable outcomes.'],
        'services.show' => [PageTemplateType::Detail, 'Service detail', 'A structured account of the challenge, approach, deliverables and benefits.'],
        'industries.index' => [PageTemplateType::Directory, 'Industries', 'Sector-aware advice grounded in institutional reality.'],
        'industries.show' => [PageTemplateType::Detail, 'Industry detail', 'Context, challenges and relevant capabilities.'],
        'experts.index' => [PageTemplateType::Directory, 'Experts', 'Meet the authorized specialists behind our work.'],
        'experts.show' => [PageTemplateType::Detail, 'Expert profile', 'Authorized professional experience, qualifications and expertise.'],
        'case-studies.index' => [PageTemplateType::Directory, 'Case studies', 'Evidence of challenges addressed and outcomes achieved.'],
        'case-studies.show' => [PageTemplateType::Detail, 'Case study', 'Challenge, approach and consented evidence of outcomes.'],
        'insights.index' => [PageTemplateType::Directory, 'Insights', 'Research and practical perspectives for decision makers.'],
        'insights.show' => [PageTemplateType::Article, 'Insight', 'A structured knowledge product with clear authorship and publication context.'],
        'events.index' => [PageTemplateType::Directory, 'Events', 'Upcoming conversations, learning sessions and convenings.'],
        'events.show' => [PageTemplateType::Event, 'Event detail', 'Schedule, venue, capacity and secure registration.'],
        'careers.index' => [PageTemplateType::Directory, 'Careers', 'Current opportunities to contribute to evidence-led change.'],
        'careers.show' => [PageTemplateType::Career, 'Vacancy detail', 'Role requirements, timing and secure application.'],
        'consultation' => [PageTemplateType::Form, 'Request a consultation', 'Tell us enough to route your challenge to the right team.'],
        'rfp' => [PageTemplateType::Form, 'Submit a request for proposal', 'Share the assignment context through the secure fixed form.'],
        'contact' => [PageTemplateType::Form, 'Contact us', 'Use the secure contact pathway for general and partnership enquiries.'],
        'legal.privacy' => [PageTemplateType::Legal, 'Privacy notice', 'How personal information is collected, used, retained and protected.'],
        'legal.terms' => [PageTemplateType::Legal, 'Terms of use', 'The conditions that govern use of this website.'],
        'legal.cookies' => [PageTemplateType::Legal, 'Cookie notice', 'Required and optional browser storage choices.'],
        'legal.accessibility' => [PageTemplateType::Legal, 'Accessibility statement', 'Our accessibility commitment, conformance target and feedback route.'],
        'search' => [PageTemplateType::Directory, 'Search', 'Search the published public information available in this language.'],
        'errors.404' => [PageTemplateType::Error, 'Page not found', 'The requested public page is unavailable.'],
        'errors.500' => [PageTemplateType::Error, 'Service interruption', 'The request could not be completed safely.'],
    ];

    public function run(): void
    {
        $actor = User::query()->whereNotNull('email_verified_at')->oldest()->first();
        if ($actor === null) {
            $this->command->warn('Page compositions were not seeded because no verified author exists.');

            return;
        }

        DB::transaction(function () use ($actor): void {
            foreach (self::PAGES as $pageKey => [$templateType, $enTitle, $enSummary]) {
                $template = PageTemplate::query()->firstOrCreate(
                    ['key' => $templateType->value],
                    [
                        'name' => str($templateType->value)->headline(),
                        'type' => $templateType,
                        'definition' => ['approved' => true, 'template' => $templateType->value],
                        'active' => true,
                    ],
                );

                foreach ([['en', $enTitle, $enSummary]] as [$locale, $title, $summary]) {
                    if (PageComposition::query()->where('page_key', $pageKey)->where('locale', $locale)->exists()) {
                        continue;
                    }

                    $composition = PageComposition::query()->create([
                        'template_id' => $template->getKey(),
                        'page_key' => $pageKey,
                        'locale' => $locale,
                        'template_type' => $templateType,
                        'state' => PageCompositionState::Published,
                        'version_no' => 1,
                        'lock_version' => 1,
                        'content_hash' => hash('sha256', "{$pageKey}|{$locale}|{$title}|{$summary}"),
                        'created_by' => $actor->getKey(),
                        'published_at' => now('UTC'),
                    ]);
                    $type = match ($templateType) {
                        PageTemplateType::Homepage => PageSectionType::HomepageHero,
                        PageTemplateType::Form => PageSectionType::FormIntroduction,
                        default => PageSectionType::PageHeader,
                    };
                    $variant = match ($type) {
                        PageSectionType::HomepageHero => 'default',
                        PageSectionType::FormIntroduction => 'standard',
                        default => 'standard',
                    };
                    $section = PageSection::query()->create([
                        'page_composition_id' => $composition->getKey(),
                        'stable_key' => $type->value,
                        'editor_label' => str($type->value)->headline(),
                        'sort_order' => 1,
                        'required' => true,
                        'locked' => true,
                    ]);
                    $content = ['heading' => $title, 'summary' => $summary];
                    $version = PageSectionVersion::query()->create([
                        'page_section_id' => $section->getKey(),
                        'version_no' => 1,
                        'locale' => $locale,
                        'type' => $type,
                        'variant' => $variant,
                        'content' => $content,
                        'presentation' => [
                            'surface_tone' => 'default',
                            'container_width' => 'standard',
                            'spacing_top' => 'standard',
                            'spacing_bottom' => 'standard',
                        ],
                        'enabled' => true,
                        'visibility_rule' => VisibilityRule::Always,
                        'selection_mode' => ContentSelectionMode::Manual,
                        'content_hash' => hash('sha256', json_encode($content, JSON_THROW_ON_ERROR)),
                        'created_by' => $actor->getKey(),
                    ]);
                    $section->forceFill(['current_version_id' => $version->getKey()])->save();
                }
            }
        });
    }
}
