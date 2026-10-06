<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Moves stored URL data from the retired /{locale}/... structure to the
 * unprefixed English-only structure.
 *
 * - Managed redirects that referenced /en/... are rewritten to the new paths.
 * - Every non-English detail URL that once existed becomes an explicit legacy
 *   decision: 301 to the same resource's published English page when one
 *   exists, otherwise 410 Gone. Nothing is sent to the homepage.
 * - Internal search documents for removed languages are deleted and English
 *   document URLs lose their /en prefix.
 * - Stored navigation items lose the retired locale parameter.
 *
 * The decisions are materialized as redirect rows so editors can inspect and
 * override them in the SEO centre.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string, 2: string}> table => [segment, parent key, parent table] */
    private const VERSIONED = [
        'service_versions' => ['services', 'service_id', 'services'],
        'industry_versions' => ['industries', 'industry_id', 'industries'],
        'expert_versions' => ['experts', 'expert_id', 'experts'],
        'case_study_versions' => ['case-studies', 'case_study_id', 'case_studies'],
        'insight_versions' => ['insights', 'insight_id', 'insights'],
    ];

    public function up(): void
    {
        $now = now('UTC');

        DB::table('redirects')->orderBy('id')->get()->each(function (object $redirect) use ($now): void {
            $source = $this->stripEnglishPrefix((string) $redirect->source_path);
            $destination = $redirect->destination_url === null
                ? null
                : $this->stripEnglishPrefix((string) $redirect->destination_url);
            if ($source === $redirect->source_path && $destination === $redirect->destination_url) {
                return;
            }
            if ($source !== $redirect->source_path
                && DB::table('redirects')->where('source_path', $source)->exists()) {
                return;
            }
            DB::table('redirects')->where('id', $redirect->id)->update([
                'source_path' => $source,
                'destination_url' => $destination,
                'updated_at' => $now,
            ]);
        });

        foreach (self::VERSIONED as $table => [$segment, $parentKey, $parentTable]) {
            DB::table($table)->where('locale', '!=', 'en')->orderBy('id')->get()
                ->each(function (object $legacy) use ($table, $segment, $parentKey, $parentTable, $now): void {
                    // Only a currently public English page is an equivalent;
                    // redirecting to an archived page would end in 410 anyway.
                    $parentPublished = DB::table($parentTable)
                        ->where('id', $legacy->{$parentKey})
                        ->where('status', 'published')
                        ->exists();
                    $english = ! $parentPublished ? null : DB::table($table)
                        ->where($parentKey, $legacy->{$parentKey})
                        ->where('locale', 'en')
                        ->where('workflow_state', 'published')
                        ->orderByDesc('version_no')
                        ->value('slug');
                    $this->legacyDecision(
                        "/{$legacy->locale}/{$segment}/{$legacy->slug}",
                        is_string($english) ? "/{$segment}/{$english}" : null,
                        $now,
                    );
                });
        }

        // Events and vacancies were stored per language without a shared
        // identity. A translation is treated as the same record only when
        // every scheduling field matches exactly; otherwise it is Gone.
        $equivalence = [
            'events' => ['events', ['starts_at', 'ends_at', 'format', 'timezone'], ['published', 'registration_open', 'registration_closed', 'completed']],
            'vacancies' => ['careers', ['type', 'opens_at', 'closes_at', 'location'], ['published']],
        ];
        foreach ($equivalence as $table => [$segment, $fields, $publicStates]) {
            DB::table($table)->where('locale', '!=', 'en')->orderBy('id')->get()
                ->each(function (object $legacy) use ($table, $segment, $fields, $publicStates, $now): void {
                    $query = DB::table($table)->where('locale', 'en')->whereIn('status', $publicStates);
                    foreach ($fields as $field) {
                        $query->where($field, $legacy->{$field});
                    }
                    $matches = $query->limit(2)->pluck('slug');
                    $this->legacyDecision(
                        "/{$legacy->locale}/{$segment}/{$legacy->slug}",
                        $matches->count() === 1 ? "/{$segment}/{$matches->first()}" : null,
                        $now,
                    );
                });
        }

        DB::table('search_documents')->where('locale', '!=', 'en')->delete();
        DB::table('search_documents')->where('url', 'like', '/en/%')->orderBy('id')->get(['id', 'url'])
            ->each(fn (object $document) => DB::table('search_documents')
                ->where('id', $document->id)
                ->update(['url' => $this->stripEnglishPrefix((string) $document->url)]));

        DB::table('page_navigation_configurations')->orderBy('id')->get()
            ->each(function (object $item): void {
                $parameters = json_decode((string) ($item->route_parameters ?? 'null'), true);
                $changes = [];
                if (is_array($parameters) && array_key_exists('locale', $parameters)) {
                    unset($parameters['locale']);
                    $changes['route_parameters'] = $parameters === [] ? null : json_encode($parameters, JSON_THROW_ON_ERROR);
                }
                if ($item->route_name === 'localized-home') {
                    $changes['route_name'] = 'home';
                }
                if ($changes !== []) {
                    DB::table('page_navigation_configurations')->where('id', $item->id)->update($changes);
                }
            });

        foreach (['primary', 'footer_explore', 'footer_engage', 'footer_legal'] as $location) {
            Cache::forget("public-navigation:{$location}:en");
        }
    }

    public function down(): void
    {
        // Legacy decisions remain valid history; English URLs are not re-prefixed.
        DB::table('redirects')->where('origin', 'legacy_locale')->delete();
    }

    private function legacyDecision(string $source, ?string $destination, mixed $now): void
    {
        $source = Str::lower($source);
        if (DB::table('redirects')->where('source_path', $source)->exists()) {
            return;
        }

        DB::table('redirects')->insert([
            'id' => (string) Str::uuid7(),
            'source_path' => $source,
            'destination_url' => $destination,
            'status_code' => $destination === null ? 410 : 301,
            'enabled' => true,
            'hit_count' => 0,
            'origin' => 'legacy_locale',
            'reason' => $destination === null
                ? 'Retired language version without an English equivalent.'
                : 'Retired language version of a published English page.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function stripEnglishPrefix(string $path): string
    {
        if ($path === '/en') {
            return '/';
        }

        return str_starts_with($path, '/en/') ? substr($path, 3) : $path;
    }
};
