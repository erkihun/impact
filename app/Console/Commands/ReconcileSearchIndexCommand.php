<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Search\SearchIndexReconciler;
use Illuminate\Console\Command;

final class ReconcileSearchIndexCommand extends Command
{
    protected $signature = 'impact:search:reconcile';

    protected $description = 'Reconcile public search documents with authoritative publication state';

    public function handle(SearchIndexReconciler $reconciler): int
    {
        $result = $reconciler->reconcile();

        $this->table(
            ['Upserted public documents', 'Deleted stale documents'],
            [[$result->upserted, $result->deleted]],
        );

        return self::SUCCESS;
    }
}
