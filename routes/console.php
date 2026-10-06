<?php

declare(strict_types=1);

use App\Services\Seo\SeoSettings;
use Illuminate\Support\Facades\Schedule;

Schedule::command('impact:content:publish-due')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('impact:search:reconcile')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('impact:privacy:cleanup-expired')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('impact:retention:inspect')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer();

if (config('impact.retention.execution_enabled') && config('impact.retention.approved_by')) {
    Schedule::command('impact:retention:inspect', [
        '--execute' => true,
        '--approved-by' => (string) config('impact.retention.approved_by'),
        '--policy-version' => (string) config('impact.retention.policy_version'),
    ])
        ->dailyAt('03:00')
        ->withoutOverlapping()
        ->onOneServer();
}

// SEO maintenance. Publication changes already rebuild the sitemap; these
// are safety nets. The cadence comes from Settings > SEO, with a safe default
// when settings cannot be read (for example before migrations run).
$sitemapSchedule = Schedule::command('seo:sitemap-generate')->withoutOverlapping()->onOneServer();
match (rescue(static fn (): string => app(SeoSettings::class)->sitemapFrequency(), 'daily', false)) {
    'hourly' => $sitemapSchedule->hourly(),
    'weekly' => $sitemapSchedule->weekly(),
    default => $sitemapSchedule->dailyAt('01:30'),
};

Schedule::command('seo:links-check')
    ->weeklyOn(1, '04:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('seo:audit --persist')
    ->dailyAt('04:30')
    ->withoutOverlapping()
    ->onOneServer();
