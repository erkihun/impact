<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\Seo\SeoIssue;
use App\Enums\Seo\SeoIssueSeverity;
use App\Services\Seo\SeoAuditService;
use Illuminate\Console\Command;

final class SeoAuditCommand extends Command
{
    protected $signature = 'seo:audit
        {--format=table : Output format: table or json}
        {--strict : Exit with a failure code when blocking defects exist}
        {--persist : Store the run for the admin SEO centre}';

    protected $description = 'Audit titles, descriptions, canonicals, robots, sitemap, structured data, headings, images, redirects, links and English-only URL hygiene';

    public function handle(SeoAuditService $audit): int
    {
        $format = (string) $this->option('format');
        if (! in_array($format, ['table', 'json'], true)) {
            $this->components->error('Use --format=table or --format=json.');

            return self::INVALID;
        }

        $report = $audit->run(persist: (bool) $this->option('persist'));
        $result = $report['result'];

        if ($format === 'json') {
            $this->line((string) json_encode([
                'status' => $result->status(),
                'metrics' => $report['metrics'],
                'issues' => $result->toArray()['issues'],
                'pages' => $report['pages'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['Page', 'Status', 'Title', 'Robots (production)', 'H1', 'Structured data'],
                collect($report['pages'])->map(static fn (array $page): array => [
                    $page['path'],
                    $page['status'],
                    mb_strimwidth((string) $page['title'], 0, 60, '…'),
                    $page['robots'],
                    count($page['h1']),
                    implode(', ', array_diff($page['structuredData'], ['Organization', 'WebSite'])),
                ])->all(),
            );

            $issues = $result->issues();
            if ($issues !== []) {
                $this->table(
                    ['Severity', 'Check', 'Where', 'Finding'],
                    array_map(static fn (SeoIssue $issue): array => [
                        $issue->severity->label(),
                        $issue->code,
                        $issue->url ?? $issue->subject ?? 'site',
                        mb_strimwidth($issue->message, 0, 110, '…'),
                    ], $issues),
                );
            }

            $metrics = $report['metrics'];
            $this->components->twoColumnDetail('Indexable pages', (string) $metrics['indexable_pages']);
            $this->components->twoColumnDetail('Blocking issues', (string) $metrics['blocking']);
            $this->components->twoColumnDetail('Warnings', (string) $metrics['warnings']);
            $this->components->twoColumnDetail('Information', (string) $metrics['information']);
            $this->components->twoColumnDetail('Indexing allowed here', $metrics['indexing_allowed'] ? 'yes' : 'no (non-production)');
            $this->components->twoColumnDetail('Canonical base URL', (string) $metrics['canonical_base_url']);
            $this->newLine();
            $result->hasBlocking()
                ? $this->components->error($result->statusLabel())
                : $this->components->info($result->statusLabel());
        }

        return $this->option('strict') && $result->count(SeoIssueSeverity::Blocking) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
