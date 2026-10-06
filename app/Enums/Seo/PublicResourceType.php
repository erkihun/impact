<?php

declare(strict_types=1);

namespace App\Enums\Seo;

use App\Models\CaseStudyVersion;
use App\Models\Event;
use App\Models\ExpertVersion;
use App\Models\IndustryVersion;
use App\Models\InsightVersion;
use App\Models\ServiceVersion;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Model;

/**
 * Every public resource family that owns an indexable detail URL.
 *
 * This is the single map between content records, their public route, the
 * sitemap segment that lists them and the schema.org type that describes
 * them, so URLs are never assembled by hand elsewhere.
 */
enum PublicResourceType: string
{
    case Service = 'service';
    case Industry = 'industry';
    case Expert = 'expert';
    case CaseStudy = 'case_study';
    case Insight = 'insight';
    case Event = 'event';
    case Vacancy = 'vacancy';

    /** @return class-string<Model> */
    public function modelClass(): string
    {
        return match ($this) {
            self::Service => ServiceVersion::class,
            self::Industry => IndustryVersion::class,
            self::Expert => ExpertVersion::class,
            self::CaseStudy => CaseStudyVersion::class,
            self::Insight => InsightVersion::class,
            self::Event => Event::class,
            self::Vacancy => Vacancy::class,
        };
    }

    /**
     * Column that groups versions of one resource. Events and vacancies are
     * not versioned, so the record is its own identity.
     */
    public function parentKey(): string
    {
        return match ($this) {
            self::Service => 'service_id',
            self::Industry => 'industry_id',
            self::Expert => 'expert_id',
            self::CaseStudy => 'case_study_id',
            self::Insight => 'insight_id',
            self::Event, self::Vacancy => 'id',
        };
    }

    public function isVersioned(): bool
    {
        return ! in_array($this, [self::Event, self::Vacancy], true);
    }

    public function showRoute(): string
    {
        return $this->segment().'.show';
    }

    public function indexRoute(): string
    {
        return $this->segment().'.index';
    }

    /** URL path segment, also used as the sitemap file name. */
    public function segment(): string
    {
        return match ($this) {
            self::Service => 'services',
            self::Industry => 'industries',
            self::Expert => 'experts',
            self::CaseStudy => 'case-studies',
            self::Insight => 'insights',
            self::Event => 'events',
            self::Vacancy => 'careers',
        };
    }

    public function titleField(): string
    {
        return match ($this) {
            self::Service, self::Industry => 'name',
            self::Expert => 'display_name',
            default => 'title',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Service => 'Service',
            self::Industry => 'Industry',
            self::Expert => 'Expert',
            self::CaseStudy => 'Case study',
            self::Insight => 'Insight',
            self::Event => 'Event',
            self::Vacancy => 'Vacancy',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Service => 'Services',
            self::Industry => 'Industries',
            self::Expert => 'Experts',
            self::CaseStudy => 'Case studies',
            self::Insight => 'Insights',
            self::Event => 'Events',
            self::Vacancy => 'Careers',
        };
    }

    public static function fromSegment(string $segment): ?self
    {
        foreach (self::cases() as $type) {
            if ($type->segment() === $segment) {
                return $type;
            }
        }

        return null;
    }

    public static function fromModel(Model $model): ?self
    {
        foreach (self::cases() as $type) {
            if ($model instanceof ($type->modelClass())) {
                return $type;
            }
        }

        return null;
    }
}
