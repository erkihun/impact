<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\Seo\SeoIssue;
use App\Enums\Seo\SeoIssueSeverity;
use App\Services\Seo\RedirectAuditor;
use Illuminate\Console\Command;

final class SeoRedirectsValidateCommand extends Command
{
    protected $signature = 'seo:redirects-validate {--fix : Flatten redirect chains to a single hop}';

    protected $description = 'Detect redirect loops, chains, unsafe or dead targets and redirects shadowing live pages';

    public function handle(RedirectAuditor $auditor): int
    {
        if ($this->option('fix')) {
            $this->components->info($auditor->flatten().' redirect chain(s) flattened.');
        }

        $result = $auditor->validate();
        $issues = $result->issues();
        if ($issues === []) {
            $this->components->info('All redirects are single-hop, local and loop-free.');

            return self::SUCCESS;
        }

        $this->table(['Severity', 'Check', 'Source', 'Finding'], array_map(static fn (SeoIssue $issue): array => [
            $issue->severity->label(), $issue->code, $issue->url, $issue->message,
        ], $issues));

        return $result->count(SeoIssueSeverity::Blocking) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
