<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\Seo\SitemapEntry;
use App\Enums\Seo\RobotsDirective;
use App\Services\Seo\CanonicalUrlBuilder;
use App\Services\Seo\PageInspector;
use App\Services\Seo\SitemapBuilder;
use DOMDocument;
use Illuminate\Console\Command;

final class SeoSitemapValidateCommand extends Command
{
    protected $signature = 'seo:sitemap-validate';

    protected $description = 'Validate sitemap XML and confirm every listed URL is a 200, indexable, self-canonical English page';

    public function handle(SitemapBuilder $sitemaps, PageInspector $inspector, CanonicalUrlBuilder $urls): int
    {
        $errors = [];
        $all = $sitemaps->all();
        $segments = [];
        foreach ($all as $segment => $entries) {
            if ($entries->isNotEmpty()) {
                $segments[$segment] = null;
            }
        }
        $index = $sitemaps->stored('index') ?? $sitemaps->indexXml($segments);
        $errors = [...$errors, ...$this->xmlErrors('sitemap.xml', $index, 'sitemapindex')];

        $total = 0;
        foreach ($all as $segment => $entries) {
            if ($entries->isEmpty()) {
                continue;
            }
            $xml = $sitemaps->stored($segment) ?? $sitemaps->urlsetXml($entries);
            $errors = [...$errors, ...$this->xmlErrors("{$segment}.xml", $xml, 'urlset')];
            if ($entries->count() > 50000 || strlen($xml) > 50 * 1024 * 1024) {
                $errors[] = "{$segment}.xml exceeds the 50,000 URL / 50 MB limit.";
            }

            /** @var SitemapEntry $entry */
            foreach ($entries as $entry) {
                $total++;
                $path = (string) parse_url($entry->loc, PHP_URL_PATH);
                if (preg_match('#^/(en|am)(/|$)#', $path) === 1 || str_contains($entry->loc, '?')) {
                    $errors[] = "{$entry->loc}: retired language or query URL.";
                }
                $page = $inspector->inspect($urls->normalizePath($path));
                if ($page['status'] !== 200) {
                    $errors[] = "{$entry->loc}: HTTP {$page['status']}.";

                    continue;
                }
                if (($page['seo']['canonical'] ?? null) !== $entry->loc) {
                    $errors[] = "{$entry->loc}: canonical is ".($page['seo']['canonical'] ?? 'missing').'.';
                }
                if (RobotsDirective::parse($page['seo']['intendedRobots'] ?? null) !== RobotsDirective::IndexFollow) {
                    $errors[] = "{$entry->loc}: not indexable.";
                }
            }
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $this->components->info("Sitemap valid: {$total} URL(s), all 200, indexable and self-canonical.");

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function xmlErrors(string $name, string $xml, string $root): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $loaded = $document->loadXML($xml, LIBXML_NONET);
        $messages = array_map(static fn ($error): string => "{$name}: ".trim($error->message), libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return $messages ?: ["{$name}: not well-formed XML."];
        }
        if ($document->documentElement?->localName !== $root
            || $document->documentElement->namespaceURI !== 'http://www.sitemaps.org/schemas/sitemap/0.9') {
            return ["{$name}: root must be <{$root}> in the sitemaps.org 0.9 namespace."];
        }

        return [];
    }
}
