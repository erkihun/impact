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
        'home' => [PageTemplateType::Homepage, 'Evidence for the decisions that shape institutions.', 'ለተቋማት የወደፊት አቅጣጫ በሚወስኑ ውሳኔዎች ላይ የተመሠረተ ማስረጃ።', 'We turn complex questions into focused strategy, stronger delivery and lasting capability.', 'ውስብስብ ጥያቄዎችን ወደ ግልጽ ስትራቴጂ፣ ጠንካራ ትግበራ እና ዘላቂ አቅም እንለውጣለን።'],
        'about' => [PageTemplateType::Institutional, 'Independent thinking, rooted in context.', 'በአውዱ ላይ የተመሠረተ ገለልተኛ አስተሳሰብ።', 'Strategy, sector expertise and implementation discipline for sustained results.', 'ለዘላቂ ውጤት ስትራቴጂ፣ የዘርፍ እውቀት እና የትግበራ ሥርዓት።'],
        'services.index' => [PageTemplateType::Directory, 'Consulting services', 'የማማከር አገልግሎቶች', 'Capabilities organized around client challenges and measurable outcomes.', 'በደንበኞች ፈተናዎች እና በሚለኩ ውጤቶች ዙሪያ የተደራጁ አቅሞች።'],
        'services.show' => [PageTemplateType::Detail, 'Service detail', 'የአገልግሎት ዝርዝር', 'A structured account of the challenge, approach, deliverables and benefits.', 'የፈተናው፣ የአቀራረቡ፣ የውጤቶቹ እና የጥቅሞቹ ዝርዝር።'],
        'industries.index' => [PageTemplateType::Directory, 'Industries', 'ዘርፎች', 'Sector-aware advice grounded in institutional reality.', 'በተቋማዊ እውነታ ላይ የተመሠረተ ዘርፍ-ተኮር ምክር።'],
        'industries.show' => [PageTemplateType::Detail, 'Industry detail', 'የዘርፍ ዝርዝር', 'Context, challenges and relevant capabilities.', 'አውድ፣ ፈተናዎች እና ተዛማጅ አቅሞች።'],
        'experts.index' => [PageTemplateType::Directory, 'Experts', 'ባለሙያዎች', 'Meet the authorized specialists behind our work.', 'ከሥራችን ጀርባ ያሉትን ፈቃድ ያላቸው ባለሙያዎች ያግኙ።'],
        'experts.show' => [PageTemplateType::Detail, 'Expert profile', 'የባለሙያ መገለጫ', 'Authorized professional experience, qualifications and expertise.', 'የተፈቀደ ሙያዊ ልምድ፣ ብቃት እና እውቀት።'],
        'case-studies.index' => [PageTemplateType::Directory, 'Case studies', 'የሥራ ልምዶች', 'Evidence of challenges addressed and outcomes achieved.', 'የተፈቱ ፈተናዎች እና የተገኙ ውጤቶች ማስረጃ።'],
        'case-studies.show' => [PageTemplateType::Detail, 'Case study', 'የሥራ ልምድ', 'Challenge, approach and consented evidence of outcomes.', 'ፈተና፣ አቀራረብ እና በፈቃድ የቀረበ የውጤት ማስረጃ።'],
        'insights.index' => [PageTemplateType::Directory, 'Insights', 'ግንዛቤዎች', 'Research and practical perspectives for decision makers.', 'ለውሳኔ ሰጪዎች ጥናት እና ተግባራዊ እይታዎች።'],
        'insights.show' => [PageTemplateType::Article, 'Insight', 'ግንዛቤ', 'A structured knowledge product with clear authorship and publication context.', 'ግልጽ ደራሲነት እና የህትመት አውድ ያለው የእውቀት ምርት።'],
        'events.index' => [PageTemplateType::Directory, 'Events', 'ዝግጅቶች', 'Upcoming conversations, learning sessions and convenings.', 'ቀጣይ ውይይቶች፣ የመማር ክፍለ ጊዜዎች እና ስብሰባዎች።'],
        'events.show' => [PageTemplateType::Event, 'Event detail', 'የዝግጅት ዝርዝር', 'Schedule, venue, capacity and secure registration.', 'መርሐ ግብር፣ ቦታ፣ አቅም እና ደህንነቱ የተጠበቀ ምዝገባ።'],
        'careers.index' => [PageTemplateType::Directory, 'Careers', 'የሥራ ዕድሎች', 'Current opportunities to contribute to evidence-led change.', 'በማስረጃ ለሚመራ ለውጥ አስተዋጽኦ የማድረግ ወቅታዊ ዕድሎች።'],
        'careers.show' => [PageTemplateType::Career, 'Vacancy detail', 'የሥራ ክፍት ቦታ ዝርዝር', 'Role requirements, timing and secure application.', 'የሥራ መስፈርቶች፣ ጊዜ እና ደህንነቱ የተጠበቀ ማመልከቻ።'],
        'consultation' => [PageTemplateType::Form, 'Request a consultation', 'የማማከር ጥያቄ ያቅርቡ', 'Tell us enough to route your challenge to the right team.', 'ፈተናዎን ወደ ትክክለኛው ቡድን ለመምራት የሚያስፈልገንን መረጃ ይስጡን።'],
        'rfp' => [PageTemplateType::Form, 'Submit a request for proposal', 'የፕሮፖዛል ጥያቄ ያቅርቡ', 'Share the assignment context through the secure fixed form.', 'የሥራውን አውድ በደህንነቱ በተጠበቀው ቋሚ ቅጽ ያጋሩ።'],
        'contact' => [PageTemplateType::Form, 'Contact us', 'ያግኙን', 'Use the secure contact pathway for general and partnership enquiries.', 'ለአጠቃላይ እና ለአጋርነት ጥያቄዎች ደህንነቱ የተጠበቀውን መንገድ ይጠቀሙ።'],
        'legal.privacy' => [PageTemplateType::Legal, 'Privacy notice', 'የግላዊነት ማስታወቂያ', 'How personal information is collected, used, retained and protected.', 'የግል መረጃ እንዴት እንደሚሰበሰብ፣ እንደሚጠቀም፣ እንደሚቆይ እና እንደሚጠበቅ።'],
        'legal.terms' => [PageTemplateType::Legal, 'Terms of use', 'የአጠቃቀም ውሎች', 'The conditions that govern use of this website.', 'የዚህን ድረ ገጽ አጠቃቀም የሚመሩ ሁኔታዎች።'],
        'legal.cookies' => [PageTemplateType::Legal, 'Cookie notice', 'የኩኪ ማስታወቂያ', 'Required and optional browser storage choices.', 'አስፈላጊ እና አማራጭ የአሳሽ ማከማቻ ምርጫዎች።'],
        'legal.accessibility' => [PageTemplateType::Legal, 'Accessibility statement', 'የተደራሽነት መግለጫ', 'Our accessibility commitment, conformance target and feedback route.', 'የተደራሽነት ቁርጠኝነታችን፣ የማክበር ግብ እና የግብረመልስ መንገድ።'],
        'search' => [PageTemplateType::Directory, 'Search', 'ፍለጋ', 'Search the published public information available in this language.', 'በዚህ ቋንቋ የታተመውን የሕዝብ መረጃ ይፈልጉ።'],
        'errors.404' => [PageTemplateType::Error, 'Page not found', 'ገጹ አልተገኘም', 'The requested public page is unavailable.', 'የጠየቁት የሕዝብ ገጽ አይገኝም።'],
        'errors.500' => [PageTemplateType::Error, 'Service interruption', 'የአገልግሎት መቋረጥ', 'The request could not be completed safely.', 'ጥያቄው በደህንነት ሊጠናቀቅ አልቻለም።'],
    ];

    public function run(): void
    {
        $actor = User::query()->whereNotNull('email_verified_at')->oldest()->first();
        if ($actor === null) {
            $this->command->warn('Page compositions were not seeded because no verified author exists.');

            return;
        }

        DB::transaction(function () use ($actor): void {
            foreach (self::PAGES as $pageKey => [$templateType, $enTitle, $amTitle, $enSummary, $amSummary]) {
                $template = PageTemplate::query()->firstOrCreate(
                    ['key' => $templateType->value],
                    [
                        'name' => str($templateType->value)->headline(),
                        'type' => $templateType,
                        'definition' => ['approved' => true, 'template' => $templateType->value],
                        'active' => true,
                    ],
                );

                foreach ([['en', $enTitle, $enSummary], ['am', $amTitle, $amSummary]] as [$locale, $title, $summary]) {
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
