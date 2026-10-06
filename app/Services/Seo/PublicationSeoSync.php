<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Contracts\CdnPurger;
use App\Jobs\Seo\GenerateSitemapsJob;
use App\Jobs\Seo\ReconcileSearchIndexJob;
use Illuminate\Support\Facades\DB;

/**
 * Propagates a change to what is public: sitemap rebuild, internal search
 * reconciliation and CDN purge of the affected URLs. Work is collected for
 * the current request and dispatched once, after the database commit, so a
 * rolled-back change never reaches crawlers or search.
 */
final class PublicationSeoSync
{
    /** @var array<string, true> */
    private array $paths = [];

    private bool $scheduled = false;

    public function __construct(
        private readonly CdnPurger $cdn,
        private readonly CanonicalUrlBuilder $urls,
    ) {}

    /** @param  list<string|null>  $paths  public paths whose state changed */
    public function changed(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            $this->paths[$this->urls->normalizePath((string) $path)] = true;
        }

        if ($this->scheduled) {
            return;
        }
        $this->scheduled = true;

        DB::afterCommit(function (): void {
            $paths = array_keys($this->paths);
            $this->paths = [];
            $this->scheduled = false;

            GenerateSitemapsJob::dispatch();
            ReconcileSearchIndexJob::dispatch();
            $this->cdn->purge([
                ...array_map(fn (string $path): string => $this->urls->url($path), $paths),
                $this->urls->forRoute('sitemap.index'),
            ]);
        });
    }
}
