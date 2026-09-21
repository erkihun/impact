<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Content\PublishScheduledContentJob;
use App\Models\PublicationSchedule;
use Illuminate\Console\Command;

final class PublishDueContentCommand extends Command
{
    protected $signature = 'impact:content:publish-due';

    protected $description = 'Dispatch idempotent publication jobs for due content schedules';

    public function handle(): int
    {
        $count = 0;
        PublicationSchedule::query()
            ->where(function ($query): void {
                $query->where(function ($pending): void {
                    $pending->where('status', 'pending')
                        ->where('publish_at', '<=', now('UTC'));
                })->orWhere(function ($published): void {
                    $published->where('status', 'published')
                        ->whereNotNull('unpublish_at')
                        ->where('unpublish_at', '<=', now('UTC'));
                });
            })
            ->eachById(function (PublicationSchedule $schedule) use (&$count): void {
                PublishScheduledContentJob::dispatch((string) $schedule->id);
                $count++;
            }, 100);

        $this->info("Dispatched {$count} due publication schedule(s).");

        return self::SUCCESS;
    }
}
