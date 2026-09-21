<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Settings\SettingsVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class VerifySettingsIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:verify
        {--format=table : Output format: table or json}
        {--strict : Return a non-zero exit code when blocking defects exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify registry, effective values, consumers, translations, and safe configuration';

    /**
     * Execute the console command.
     */
    public function handle(SettingsVerificationService $verification): int
    {
        $format = (string) $this->option('format');
        if (! in_array($format, ['table', 'json'], true)) {
            $this->error('The format must be table or json.');

            return self::INVALID;
        }

        $report = $verification->verify();
        Cache::put('settings.verification.last', $report, now()->addDay());

        if ($format === 'json') {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->table(
                ['Metric', 'Count'],
                collect($report['summary'])->map(
                    fn (int $count, string $metric): array => [$metric, $count],
                )->values()->all(),
            );
            $this->table(
                ['Severity', 'Code', 'Key', 'Message'],
                collect($report['issues'])->map(fn (array $issue): array => [
                    $issue['severity'],
                    $issue['code'],
                    $issue['key'] ?? '-',
                    $issue['message'],
                ])->all(),
            );
        }

        return $this->option('strict') && $report['summary']['error_count'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
