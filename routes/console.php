<?php

declare(strict_types=1);

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
