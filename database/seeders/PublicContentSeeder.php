<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CaseStudy;
use App\Models\CaseStudyVersion;
use App\Models\ContentRelation;
use App\Models\EngagementSubmission;
use App\Models\Event;
use App\Models\Expert;
use App\Models\ExpertVersion;
use App\Models\Industry;
use App\Models\IndustryVersion;
use App\Models\Insight;
use App\Models\InsightVersion;
use App\Models\Office;
use App\Models\Service;
use App\Models\ServiceVersion;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Search\SearchIndexReconciler;
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
                ['STRATEGY', 'strategy-transformation', 'Strategy and transformation', 'Translate ambition into a focused strategy, operating model and delivery roadmap.'],
                ['INSTITUTION', 'institutional-strengthening', 'Institutional strengthening', 'Build the systems, structures and capabilities that make performance sustainable.'],
                ['RESEARCH', 'research-learning', 'Research, monitoring and learning', 'Use credible evidence to make decisions, learn quickly and demonstrate impact.'],
            ] as $index => [$code, $slug, $name, $summary]) {
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

                ] as [$locale, $localizedSlug, $localizedName, $localizedSummary]) {
                    ServiceVersion::query()->create([
                        'service_id' => $service->id,
                        'locale' => $locale,
                        'version_no' => 1,
                        'slug' => $localizedSlug,
                        'name' => $localizedName,
                        'summary' => $localizedSummary,
                        'problem_statement' => $localizedSummary,
                        'approach' => 'We combine diagnosis, co-design and disciplined implementation. Every engagement includes clear governance, practical milestones and capability transfer.',
                        'deliverables' => ['Diagnostic', 'Strategy', 'Delivery roadmap'],
                        'benefits' => 'Stronger decisions, clearer accountability and measurable progress.',
                        'cta_label' => 'Discuss this service',
                        'workflow_state' => 'published',
                    ]);

                }
            }

            foreach ([
                ['PUBLIC', 'public-institutions', 'Public institutions', 'Advice for ministries, agencies and regulators on reform priorities, delivery systems and institutional performance.'],
                ['SOCIAL', 'social-impact', 'Social impact', 'Support for foundations, NGOs and programmes that need clear strategy, credible evidence and accountable delivery.'],
                ['BUSINESS', 'responsible-business', 'Responsible business', 'Guidance for companies aligning growth, governance and community impact with transparent, measurable commitments.'],
            ] as $index => [$code, $slug, $name, $industrySummary]) {
                $industry = Industry::query()->create([
                    'code' => $code,
                    'status' => 'published',
                    'sort_order' => $index + 1,
                    'featured' => true,
                ]);

                foreach ([['en', $slug, $name]] as [$locale, $localizedSlug, $localizedName]) {
                    IndustryVersion::query()->create([
                        'industry_id' => $industry->id,
                        'locale' => $locale,
                        'version_no' => 1,
                        'slug' => $localizedSlug,
                        'name' => $localizedName,
                        'summary' => $industrySummary,
                        'overview' => 'Our specialists combine sector knowledge with strategy, organizational design, research and implementation expertise.',
                        'challenges' => 'Complex mandates, constrained resources, shifting stakeholder expectations and the need for measurable outcomes.',
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

            collect([
                [
                    'slug' => 'selam-tadesse',
                    'display_name' => 'Selam Tadesse',
                    'professional_title' => 'Strategy and Institutional Transformation Lead',
                    'biography' => 'Selam helps leadership teams translate public value goals into operating models, delivery systems and measurable outcomes.',
                    'qualifications' => ['MSc Public Policy', 'Certified Change Practitioner'],
                    'years_experience' => 15,
                ],
                [
                    'slug' => 'dawit-bekele',
                    'display_name' => 'Dawit Bekele',
                    'professional_title' => 'Research, Monitoring and Learning Advisor',
                    'biography' => 'Dawit designs evidence systems that help teams test assumptions, understand progress and make decisions with confidence.',
                    'qualifications' => ['MA Development Studies', 'Evaluation Design Specialist'],
                    'years_experience' => 12,
                ],
                [
                    'slug' => 'mihret-abebe',
                    'display_name' => 'Mihret Abebe',
                    'professional_title' => 'Organizational Strengthening Specialist',
                    'biography' => 'Mihret works with institutions to clarify roles, improve delivery routines and build practical management capacity.',
                    'qualifications' => ['MBA Organizational Leadership', 'Certified Facilitation Practitioner'],
                    'years_experience' => 10,
                ],
                [
                    'slug' => 'yonas-tesfaye',
                    'display_name' => 'Yonas Tesfaye',
                    'professional_title' => 'Sector Strategy and Delivery Consultant',
                    'biography' => 'Yonas connects sector context, stakeholder incentives and implementation planning so strategies can move into disciplined action.',
                    'qualifications' => ['MPA Public Administration', 'Programme Delivery Professional'],
                    'years_experience' => 14,
                ],
            ])->each(function (array $profile, int $index): void {
                $expert = Expert::query()->create([
                    'status' => 'published',
                    'public_email_enabled' => false,
                    'years_experience' => $profile['years_experience'],
                    'publication_authorized_at' => now(),
                    'authorization_reference' => 'DEMO-EXPERT-CONSENT-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                ]);

                ExpertVersion::query()->create([
                    'expert_id' => $expert->id,
                    'locale' => 'en',
                    'version_no' => 1,
                    'slug' => $profile['slug'],
                    'display_name' => $profile['display_name'],
                    'professional_title' => $profile['professional_title'],
                    'biography' => $profile['biography'],
                    'qualifications' => $profile['qualifications'],
                    'languages' => ['English', 'Amharic'],
                    'workflow_state' => 'published',
                ]);
            });

            $event = Event::query()->create([
                'status' => 'registration_open',
                'format' => 'hybrid',
                'title' => 'From strategy to delivery: practical leadership systems',
                'slug' => 'strategy-to-delivery',
                'locale' => 'en',
                'description' => 'A practical session on turning leadership priorities into visible delivery routines and learning cycles.',
                'starts_at' => now('UTC')->addMonth(),
                'ends_at' => now('UTC')->addMonth()->addHours(2),
                'timezone' => 'Africa/Addis_Ababa',
                'venue' => 'Addis Ababa and online',
                'capacity' => 100,
                'registration_closes_at' => now('UTC')->addMonth()->subDay(),
            ]);

            Vacancy::query()->create([
                'reference_no' => 'ICO-CAR-2026-EN',
                'status' => 'published',
                'title' => 'Senior Consultant',
                'slug' => 'senior-consultant',
                'locale' => 'en',
                'type' => 'full_time',
                'location' => 'Addis Ababa',
                'description' => 'Lead evidence-driven strategy, organizational strengthening and implementation engagements.',
                'requirements' => 'Relevant postgraduate qualification, strong consulting judgment and demonstrated delivery experience.',
                'opens_at' => now('UTC')->subDay(),
                'closes_at' => now('UTC')->addMonth(),
                'application_limit' => 200,
            ]);

            $this->relate($author->id, $event);

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

        // Search documents are derived from published content, never hand-written.
        app(SearchIndexReconciler::class)->reconcile();
    }

    /**
     * Real relationships between the fixtures so detail pages carry
     * meaningful contextual links instead of being reachable only from
     * listings.
     */
    private function relate(string $authorId, Event $event): void
    {
        $service = static fn (string $slug): string => (string) ServiceVersion::query()->where('slug', $slug)->value('service_id');
        $industry = static fn (string $slug): string => (string) IndustryVersion::query()->where('slug', $slug)->value('industry_id');
        $expert = static fn (string $slug): string => (string) ExpertVersion::query()->where('slug', $slug)->value('expert_id');
        $caseStudy = (string) CaseStudyVersion::query()->where('slug', 'delivery-system-for-national-programme')->value('case_study_id');
        $insight = (string) InsightVersion::query()->where('slug', 'strategy-that-survives-contact-with-reality')->value('insight_id');

        foreach ([
            ['strategy-transformation', 'public-institutions'],
            ['strategy-transformation', 'responsible-business'],
            ['institutional-strengthening', 'public-institutions'],
            ['institutional-strengthening', 'social-impact'],
            ['research-learning', 'social-impact'],
            ['research-learning', 'public-institutions'],
        ] as $index => [$serviceSlug, $industrySlug]) {
            DB::table('service_industry')->insert([
                'service_id' => $service($serviceSlug),
                'industry_id' => $industry($industrySlug),
                'sort_order' => $index,
                'featured' => false,
            ]);
        }

        foreach ([
            ['selam-tadesse', 'strategy-transformation'],
            ['selam-tadesse', 'institutional-strengthening'],
            ['dawit-bekele', 'research-learning'],
            ['mihret-abebe', 'institutional-strengthening'],
            ['yonas-tesfaye', 'strategy-transformation'],
        ] as $index => [$expertSlug, $serviceSlug]) {
            DB::table('expert_service')->insert([
                'expert_id' => $expert($expertSlug),
                'service_id' => $service($serviceSlug),
                'sort_order' => $index,
                'featured' => false,
            ]);
        }

        foreach ([
            ['case_study', $caseStudy, 'service', $service('strategy-transformation')],
            ['case_study', $caseStudy, 'service', $service('research-learning')],
            ['case_study', $caseStudy, 'industry', $industry('public-institutions')],
            ['case_study', $caseStudy, 'expert', $expert('selam-tadesse')],
            ['case_study', $caseStudy, 'expert', $expert('yonas-tesfaye')],
            ['insight', $insight, 'service', $service('strategy-transformation')],
            ['insight', $insight, 'expert', $expert('selam-tadesse')],
            ['insight', $insight, 'industry', $industry('public-institutions')],
            ['event', (string) $event->id, 'service', $service('strategy-transformation')],
            ['event', (string) $event->id, 'insight', $insight],
            ['event', (string) $event->id, 'expert', $expert('mihret-abebe')],
        ] as $index => [$sourceType, $sourceId, $targetType, $targetId]) {
            ContentRelation::query()->create([
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'sort_order' => $index,
                'created_by' => $authorId,
            ]);
        }
    }
}
