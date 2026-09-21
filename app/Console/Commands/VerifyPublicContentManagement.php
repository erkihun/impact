<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MediaStatus;
use App\Enums\MediaVisibility;
use App\Enums\PageCompositionState;
use App\Enums\PageSectionType;
use App\Models\PageComposition;
use App\Models\PageNavigationConfiguration;
use App\Models\PageSection;
use App\Models\PageSectionVersion;
use App\Support\PageSectionRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

final class VerifyPublicContentManagement extends Command
{
    protected $signature = 'public-content:verify
        {--strict : Return a failing exit code for warnings as well as errors}
        {--locale=* : Limit verification to one or more enabled locales}';

    protected $description = 'Verify that every public page has a valid, publishable structured composition.';

    /** @var list<string> */
    private const REQUIRED_PAGE_KEYS = [
        'home', 'about',
        'services.index', 'services.show', 'industries.index', 'industries.show',
        'experts.index', 'experts.show', 'case-studies.index', 'case-studies.show',
        'insights.index', 'insights.show', 'events.index', 'events.show',
        'careers.index', 'careers.show', 'consultation', 'rfp', 'contact',
        'legal.privacy', 'legal.terms', 'legal.cookies', 'legal.accessibility',
        'search', 'errors.404', 'errors.500',
    ];

    public function handle(PageSectionRegistry $registry): int
    {
        if (! Schema::hasTable('page_compositions')) {
            $this->error('Page-composition tables are missing. Run the database migrations.');

            return self::FAILURE;
        }

        $errors = [];
        $warnings = [];
        $locales = collect((array) $this->option('locale'))
            ->filter()
            ->whenEmpty(fn ($items) => $items->push('en')->push('am'))
            ->unique()
            ->values();

        $registered = collect($registry->all());
        $enumTypes = collect(PageSectionType::cases())->map->value;
        foreach ($enumTypes->diff($registered->keys()) as $missing) {
            $errors[] = "Section enum [{$missing}] has no registry definition.";
        }
        foreach ($registered as $type => $definition) {
            if (! view()->exists($definition['renderer'])) {
                $errors[] = "Section [{$type}] renderer [{$definition['renderer']}] does not exist.";
            }
        }

        foreach ($locales as $locale) {
            foreach (['primary', 'footer_explore', 'footer_engage', 'footer_legal'] as $location) {
                $navigation = PageNavigationConfiguration::query()
                    ->where('locale', $locale)
                    ->where('location', $location)
                    ->where('enabled', true)
                    ->whereNotNull('published_at')
                    ->orderBy('sort_order')
                    ->get();
                if ($navigation->isEmpty()) {
                    $errors[] = "No published navigation items for [{$location}] [{$locale}].";
                }
                if ($navigation->pluck('sort_order')->duplicates()->isNotEmpty()) {
                    $errors[] = "Navigation [{$location}] [{$locale}] has duplicate order values.";
                }
                foreach ($navigation as $item) {
                    if (! Route::has($item->route_name)) {
                        $errors[] = "Navigation item [{$item->label}] references missing route [{$item->route_name}].";
                    }
                }
            }

            foreach (self::REQUIRED_PAGE_KEYS as $pageKey) {
                $published = PageComposition::query()
                    ->where('page_key', $pageKey)
                    ->where('locale', $locale)
                    ->where('state', PageCompositionState::Published)
                    ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now('UTC')))
                    ->where(fn ($query) => $query->whereNull('unpublish_at')->orWhere('unpublish_at', '>', now('UTC')))
                    ->with(['sections.currentVersion.media.asset', 'sections.currentVersion.actions'])
                    ->get();

                if ($published->isEmpty()) {
                    $errors[] = "No active published composition for [{$pageKey}] [{$locale}].";

                    continue;
                }
                if ($published->count() > 1) {
                    $errors[] = "More than one active published composition for [{$pageKey}] [{$locale}].";
                }

                $composition = $published->sortByDesc('version_no')->first();
                $headingSections = $composition->sections->filter(
                    function (PageSection $section): bool {
                        if ($section->getAttribute('current_version_id') === null) {
                            return false;
                        }

                        return in_array(
                            $section->currentVersion->type,
                            [PageSectionType::HomepageHero, PageSectionType::PageHeader, PageSectionType::FormIntroduction],
                            true,
                        ) && $section->currentVersion->enabled;
                    },
                );
                if ($headingSections->count() !== 1) {
                    $errors[] = "Composition [{$pageKey}] [{$locale}] must have exactly one enabled H1 section.";
                }
                if ($composition->sections->isEmpty()) {
                    $errors[] = "Composition [{$pageKey}] [{$locale}] has no sections.";
                }

                foreach ($composition->sections as $section) {
                    if ($section->getAttribute('current_version_id') === null) {
                        $errors[] = "Section [{$section->stable_key}] has no current immutable version.";

                        continue;
                    }
                    $version = $section->currentVersion;
                    if ($version->locale !== $locale) {
                        $errors[] = "Section [{$section->stable_key}] locale does not match its composition.";
                    }
                    try {
                        $registry->validate($version->type, $version->variant, $version->content, $version->presentation);
                    } catch (\Throwable $exception) {
                        $errors[] = "Section [{$section->stable_key}] is invalid: {$exception->getMessage()}";
                    }
                    if ($version->visible_from && $version->visible_until && $version->visible_until <= $version->visible_from) {
                        $errors[] = "Section [{$section->stable_key}] has an invalid visibility window.";
                    }
                    foreach ($version->media as $usage) {
                        $asset = $usage->asset;
                        if ($asset->visibility !== MediaVisibility::Public
                            || $asset->scan_status !== MediaStatus::Clean
                            || $asset->processing_status !== MediaStatus::Ready) {
                            $errors[] = "Section [{$section->stable_key}] references media that is not public, clean and ready.";
                        } elseif (! $usage->decorative && blank($asset->alt_text)) {
                            $errors[] = "Section [{$section->stable_key}] has non-decorative media without alternative text.";
                        }
                    }
                    foreach ($version->actions as $action) {
                        if ($action->internal_route && ! Route::has($action->internal_route)) {
                            $errors[] = "Section [{$section->stable_key}] references missing route [{$action->internal_route}].";
                        }
                        if ($action->external_url && parse_url($action->external_url, PHP_URL_SCHEME) !== 'https') {
                            $errors[] = "Section [{$section->stable_key}] has a non-HTTPS external action.";
                        }
                        if (! $action->internal_route && ! $action->external_url && ! $action->destination_id) {
                            $errors[] = "Section [{$section->stable_key}] has an action without a destination.";
                        }
                    }
                }
            }
        }

        $orphanedVersions = PageSectionVersion::query()
            ->whereNotIn(
                'page_section_id',
                PageSection::withTrashed()->select('id'),
            )
            ->count();
        if ($orphanedVersions > 0) {
            $warnings[] = "{$orphanedVersions} orphaned section versions exist.";
        }

        foreach ($errors as $error) {
            $this->error($error);
        }
        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        $this->newLine();
        $this->components->info(sprintf(
            'Verified %d page keys across %d locale(s): %d error(s), %d warning(s).',
            count(self::REQUIRED_PAGE_KEYS),
            $locales->count(),
            count($errors),
            count($warnings),
        ));

        return $errors !== [] || ($this->option('strict') && $warnings !== [])
            ? self::FAILURE
            : self::SUCCESS;
    }
}
