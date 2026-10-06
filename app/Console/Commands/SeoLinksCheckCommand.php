<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SeoLinkCheck;
use App\Services\Seo\LinkChecker;
use Illuminate\Console\Command;

final class SeoLinksCheckCommand extends Command
{
    protected $signature = 'seo:links-check {--external : Also check external links (bounded and rate-limited)}';

    protected $description = 'Check navigation, footer, content, related, CTA, redirect-target and public-media links';

    public function handle(LinkChecker $checker): int
    {
        $summary = $checker->run((bool) $this->option('external'));

        $broken = SeoLinkCheck::query()->where('resolution_status', 'open')->orderByDesc('severity')->orderBy('url')->get();
        if ($broken->isNotEmpty()) {
            $this->table(['Severity', 'Broken URL', 'Status', 'Source', 'Link text', 'First detected'], $broken->map(static fn (SeoLinkCheck $link): array => [
                $link->severity, $link->url, $link->status_code ?? 'no response', $link->source_label.' — '.$link->source_url, $link->link_text, $link->first_detected_at->toDateString(),
            ])->all());
        }

        $this->components->twoColumnDetail('Links checked', (string) $summary['checked']);
        $this->components->twoColumnDetail('Internal links that redirect', (string) $summary['redirected']);
        $this->components->twoColumnDetail('External links checked', (string) $summary['external_checked']);
        $this->components->twoColumnDetail('Broken', (string) $summary['broken']);

        $blocking = $broken->where('severity', 'blocking')->count();
        if ($blocking > 0) {
            $this->components->error("{$blocking} broken link(s) in navigation, redirects or media.");

            return self::FAILURE;
        }
        $summary['broken'] > 0
            ? $this->components->warn('Broken content links found; see the SEO centre.')
            : $this->components->info('No broken links found.');

        return self::SUCCESS;
    }
}
