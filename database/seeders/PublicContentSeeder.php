<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CaseStudy;
use App\Models\CaseStudyVersion;
use App\Models\EngagementSubmission;
use App\Models\Event;
use App\Models\Expert;
use App\Models\ExpertVersion;
use App\Models\Industry;
use App\Models\IndustryVersion;
use App\Models\Insight;
use App\Models\InsightVersion;
use App\Models\Office;
use App\Models\SearchDocument;
use App\Models\Service;
use App\Models\ServiceVersion;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PublicContentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $email = config('impact.development_admin.email');
        $author = is_string($email)
            ? User::query()->where('email', $email)->first()
            : null;

        if ($author === null) {
            $this->command->warn(
                'Public development fixtures not created: configure the development administrator first.',
            );

            return;
        }

        DB::transaction(function () use ($author): void {
            foreach ([
                ['STRATEGY', 'strategy-transformation', 'Strategy and transformation', 'ስትራቴጂ እና ተቋማዊ ለውጥ', 'Translate ambition into a focused strategy, operating model and delivery roadmap.'],
                ['INSTITUTION', 'institutional-strengthening', 'Institutional strengthening', 'ተቋማዊ ማጠናከር', 'Build the systems, structures and capabilities that make performance sustainable.'],
                ['RESEARCH', 'research-learning', 'Research, monitoring and learning', 'ጥናት፣ ክትትል እና ትምህርት', 'Use credible evidence to make decisions, learn quickly and demonstrate impact.'],
            ] as $index => [$code, $slug, $name, $amharicName, $summary]) {
                $service = Service::query()->create([
                    'code' => $code,
                    'status' => 'published',
                    'sort_order' => $index + 1,
                    'featured' => true,
                    'created_by' => $author->id,
                    'owner_id' => $author->id,
                ]);

                foreach ([
                    ['en', $slug, $name, $summary],
                    ['am', $slug.'-am', $amharicName, 'ውስብስብ ፈተናዎችን ወደ ግልጽ፣ ተግባራዊ እና ዘላቂ ውጤት እንቀይራለን።'],
                ] as [$locale, $localizedSlug, $localizedName, $localizedSummary]) {
                    $version = ServiceVersion::query()->create([
                        'service_id' => $service->id,
                        'locale' => $locale,
                        'version_no' => 1,
                        'slug' => $localizedSlug,
                        'name' => $localizedName,
                        'summary' => $localizedSummary,
                        'problem_statement' => $localizedSummary,
                        'approach' => $locale === 'am'
                            ? 'ምርመራን፣ የጋራ ንድፍንና የተደራጀ ትግበራን እናጣምራለን። እያንዳንዱ ትብብር ግልጽ አስተዳደር፣ ተግባራዊ ምዕራፎችና የአቅም ሽግግር ያካትታል።'
                            : 'We combine diagnosis, co-design and disciplined implementation. Every engagement includes clear governance, practical milestones and capability transfer.',
                        'deliverables' => $locale === 'am'
                            ? ['ምርመራ', 'ስትራቴጂ', 'የአፈጻጸም ፍኖተ ካርታ']
                            : ['Diagnostic', 'Strategy', 'Delivery roadmap'],
                        'benefits' => $locale === 'am'
                            ? 'የተሻሉ ውሳኔዎች፣ ግልጽ ተጠያቂነትና ሊለካ የሚችል እድገት።'
                            : 'Stronger decisions, clearer accountability and measurable progress.',
                        'cta_label' => $locale === 'am' ? 'ስለዚህ አገልግሎት ይወያዩ' : 'Discuss this service',
                        'workflow_state' => 'published',
                    ]);

                    SearchDocument::query()->create([
                        'searchable_type' => 'service',
                        'searchable_id' => $version->id,
                        'locale' => $locale,
                        'title' => $localizedName,
                        'summary' => $localizedSummary,
                        'body' => $localizedSummary,
                        'url' => "/{$locale}/services/{$localizedSlug}",
                        'filters' => ['service'],
                        'published_at' => now(),
                    ]);
                }
            }

            foreach ([
                ['PUBLIC', 'public-institutions', 'Public institutions', 'የመንግሥት ተቋማት'],
                ['SOCIAL', 'social-impact', 'Social impact', 'ማኅበራዊ ተፅዕኖ'],
                ['BUSINESS', 'responsible-business', 'Responsible business', 'ኃላፊነት ያለው ንግድ'],
            ] as $index => [$code, $slug, $name, $amharicName]) {
                $industry = Industry::query()->create([
                    'code' => $code,
                    'status' => 'published',
                    'sort_order' => $index + 1,
                    'featured' => true,
                ]);

                foreach ([['en', $slug, $name], ['am', $slug.'-am', $amharicName]] as [$locale, $localizedSlug, $localizedName]) {
                    IndustryVersion::query()->create([
                        'industry_id' => $industry->id,
                        'locale' => $locale,
                        'version_no' => 1,
                        'slug' => $localizedSlug,
                        'name' => $localizedName,
                        'summary' => $locale === 'am'
                            ? 'በፖሊሲ፣ በተቋማዊ እውነታና በባለድርሻ ፍላጎቶች ላይ የተመሠረተ ዘርፍ-ተኮር ምክር።'
                            : 'Sector-aware advice grounded in policy, institutional reality and stakeholder needs.',
                        'overview' => $locale === 'am'
                            ? 'ባለሙያዎቻችን የዘርፍ እውቀትን ከስትራቴጂ፣ ከድርጅታዊ ንድፍ፣ ከጥናትና ከትግበራ ልምድ ጋር ያጣምራሉ።'
                            : 'Our specialists combine sector knowledge with strategy, organizational design, research and implementation expertise.',
                        'challenges' => $locale === 'am'
                            ? 'ውስብስብ ተልዕኮዎች፣ ውስን ሀብቶች፣ የሚለዋወጡ የባለድርሻ ተስፋዎችና ሊለኩ የሚችሉ ውጤቶች አስፈላጊነት።'
                            : 'Complex mandates, constrained resources, shifting stakeholder expectations and the need for measurable outcomes.',
                        'workflow_state' => 'published',
                    ]);
                }
            }

            $caseStudy = CaseStudy::query()->create([
                'status' => 'published',
                'client_display_mode' => 'anonymized',
                'client_consent_at' => now(),
                'authorization_reference' => 'DEMO-CONSENT-001',
                'featured' => true,
            ]);
            CaseStudyVersion::query()->create([
                'case_study_id' => $caseStudy->id,
                'locale' => 'en',
                'version_no' => 1,
                'slug' => 'delivery-system-for-national-programme',
                'title' => 'Building a delivery system for a national programme',
                'challenge' => 'A multi-agency programme needed clearer ownership, faster decisions and consistent performance information.',
                'approach' => 'We co-designed a practical delivery rhythm, an outcome framework and decision-ready reporting with the client team.',
                'outcomes' => 'Leadership gained a shared view of priorities, risks and progress while teams took ownership of corrective action.',
                'metrics' => ['workstreams' => 12, 'agencies' => 6],
                'workflow_state' => 'published',
            ]);

            $insight = Insight::query()->create([
                'type' => 'article',
                'status' => 'published',
                'featured' => true,
                'published_at' => now(),
            ]);
            InsightVersion::query()->create([
                'insight_id' => $insight->id,
                'locale' => 'en',
                'version_no' => 1,
                'slug' => 'strategy-that-survives-contact-with-reality',
                'title' => 'Strategy that survives contact with reality',
                'excerpt' => 'Four choices that turn a strategy document into an operating discipline.',
                'body' => 'The strongest strategies make trade-offs explicit, assign ownership, connect resources to priorities and create a regular learning rhythm.',
                'workflow_state' => 'published',
            ]);

            $expert = Expert::query()->create([
                'status' => 'published',
                'public_email_enabled' => false,
                'years_experience' => 15,
                'publication_authorized_at' => now(),
                'authorization_reference' => 'DEMO-EXPERT-CONSENT-001',
            ]);
            ExpertVersion::query()->create([
                'expert_id' => $expert->id,
                'locale' => 'en',
                'version_no' => 1,
                'slug' => 'selam-tadesse',
                'display_name' => 'Selam Tadesse',
                'professional_title' => 'Strategy and Institutional Transformation Lead',
                'biography' => 'Selam helps leadership teams translate public value goals into operating models, delivery systems and measurable outcomes.',
                'qualifications' => ['MSc Public Policy', 'Certified Change Practitioner'],
                'languages' => ['English', 'Amharic'],
                'workflow_state' => 'published',
            ]);

            foreach (['en', 'am'] as $locale) {
                Event::query()->create([
                    'status' => 'registration_open',
                    'format' => 'hybrid',
                    'title' => $locale === 'en'
                        ? 'From strategy to delivery: practical leadership systems'
                        : 'ከስትራቴጂ ወደ ትግበራ፦ ተግባራዊ የአመራር ሥርዓቶች',
                    'slug' => 'strategy-to-delivery-'.$locale,
                    'locale' => $locale,
                    'description' => 'A practical session on turning leadership priorities into visible delivery routines and learning cycles.',
                    'starts_at' => now('UTC')->addMonth(),
                    'ends_at' => now('UTC')->addMonth()->addHours(2),
                    'timezone' => 'Africa/Addis_Ababa',
                    'venue' => 'Addis Ababa and online',
                    'capacity' => 100,
                    'registration_closes_at' => now('UTC')->addMonth()->subDay(),
                ]);

                Vacancy::query()->create([
                    'reference_no' => 'ICO-CAR-2026-'.strtoupper($locale),
                    'status' => 'published',
                    'title' => $locale === 'en' ? 'Senior Consultant' : 'ከፍተኛ አማካሪ',
                    'slug' => 'senior-consultant-'.$locale,
                    'locale' => $locale,
                    'type' => 'full_time',
                    'location' => 'Addis Ababa',
                    'description' => 'Lead evidence-driven strategy, organizational strengthening and implementation engagements.',
                    'requirements' => 'Relevant postgraduate qualification, strong consulting judgment and demonstrated delivery experience.',
                    'opens_at' => now('UTC')->subDay(),
                    'closes_at' => now('UTC')->addMonth(),
                    'application_limit' => 200,
                ]);
            }

            Office::query()->create([
                'code' => 'ADDIS-HQ',
                'status' => 'active',
                'is_primary' => true,
                'locale' => 'en',
                'name' => 'Addis Ababa office',
                'address' => 'Bole district',
                'city' => 'Addis Ababa',
                'country' => 'Ethiopia',
                'email' => 'hello@impact.test',
                'hours' => 'Monday–Friday, 08:30–17:30',
                'timezone' => 'Africa/Addis_Ababa',
            ]);

            EngagementSubmission::query()->create([
                'reference_no' => 'CONS-DEMO-2026',
                'type' => 'consultation',
                'status' => 'received',
                'locale' => 'en',
                'contact_name' => 'Development Example',
                'organization_name' => 'Sample Institution',
                'email' => 'sample@example.test',
                'description_encrypted' => 'Safe development fixture for the engagement queue.',
                'retention_until' => now('UTC')->addDays(30),
                'submitted_at' => now('UTC'),
            ]);
        });
    }
}
