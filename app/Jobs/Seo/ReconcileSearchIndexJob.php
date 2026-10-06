<?php

declare(strict_types=1);

namespace App\Jobs\Seo;

use App\Services\Search\SearchIndexReconciler;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Brings internal search in line with what is public after a publication
 * change (publish, unpublish, archive, slug change). Unique while queued.
 */
final class ReconcileSearchIndexJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 120;

    public function __construct()
    {
        $this->onQueue('search');
    }

    public function handle(SearchIndexReconciler $reconciler): void
    {
        $reconciler->reconcile();
    }
}
