<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\Seo\SeoIssue;
use App\Enums\Seo\SeoIssueSeverity;
use App\Services\Seo\CanonicalUrlBuilder;
use App\Services\Seo\PageInspector;
use App\Services\Seo\SitemapBuilder;
use App\Services\Seo\StructuredDataValidator;
use Illuminate\Console\Command;

final class SeoStructuredDataValidateCommand extends Command
{
    protected $signature = 'seo:structured-data-validate';

    protected $description = 'Render every indexable page and validate its JSON-LD (required properties, no fabricated ratings or awards, no retired URLs)';

    public function handle(SitemapBuilder $sitemaps, PageInspector $inspector, StructuredDataValidator $validator, CanonicalUrlBuilder $urls): int
    {
        $rows = [];
        $issues = [];
        $blocking = 0;

        foreach (collect($sitemaps->all())->flatten(1) as $entry) {
            $path = $urls->normalizePath((string) parse_url($entry->loc, PHP_URL_PATH));
            $page = $inspector->inspect($path);
            $result = $validator->validateScripts($page['jsonLd'], $path);
            $blocking += $result->count(SeoIssueSeverity::Blocking);
            $issues = [...$issues, ...$result->issues()];
            $rows[] = [$path, implode(', ', $page['seo']['structuredDataTypes'] ?? []), $result->statusLabel()];
        }

        $this->table(['Page', 'Types', 'Result'], $rows);
        if ($issues !== []) {
            $this->table(['Severity', 'Page', 'Type', 'Finding'], array_map(static fn (SeoIssue $issue): array => [
                $issue->severity->label(), $issue->url, $issue->subject, $issue->message,
            ], $issues));
        }

        if ($blocking > 0) {
            $this->components->error("{$blocking} blocking structured-data issue(s).");

            return self::FAILURE;
        }
        $this->components->info('Structured data valid on '.count($rows).' page(s).');

        return self::SUCCESS;
    }
}
