<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Actions\Seo\SaveRedirectAction;
use App\Data\Seo\SeoIssue;
use App\Data\Seo\SeoValidationResult;
use App\Enums\Seo\PublicResourceType;
use App\Enums\Seo\SeoIssueSeverity;
use App\Models\Redirect;
use App\Queries\Seo\PublicResourceQuery;
use Illuminate\Support\Facades\DB;

/**
 * Integrity checks for the whole redirect table, plus chain flattening.
 */
final readonly class RedirectAuditor
{
    public function __construct(
        private RedirectResolver $resolver,
        private CanonicalUrlBuilder $urls,
        private SaveRedirectAction $redirects,
    ) {}

    public function validate(): SeoValidationResult
    {
        $result = new SeoValidationResult;

        Redirect::query()->where('enabled', true)->orderBy('source_path')->get()->each(function (Redirect $redirect) use ($result): void {
            $source = $redirect->source_path;
            if ($this->urls->normalizePath($source) !== $source) {
                $result->add(new SeoIssue('redirect-unnormalized-source', SeoIssueSeverity::Warning, 'Source path is not in canonical form (lowercase, no trailing slash).', $source));
            }
            if ($redirect->isGone()) {
                return;
            }

            $target = $this->resolver->localPath($redirect->destination_url);
            if ($target === null) {
                $result->add(new SeoIssue('redirect-unsafe-target', SeoIssueSeverity::Blocking, "Destination {$redirect->destination_url} is not a path on this site.", $source));

                return;
            }
            if (! in_array($redirect->status_code, [301, 302, 307, 308], true)) {
                $result->add(new SeoIssue('redirect-invalid-status', SeoIssueSeverity::Blocking, "Unsupported redirect status {$redirect->status_code}.", $source));
            }

            $chain = $this->resolver->finalDestination($source);
            if ($chain === null) {
                $result->add(new SeoIssue('redirect-loop', SeoIssueSeverity::Blocking, 'Redirect loop detected.', $source));

                return;
            }
            if ($chain['hops'] > 1) {
                $result->add(new SeoIssue('redirect-chain', SeoIssueSeverity::Warning, "Redirect chain of {$chain['hops']} hops; run seo:redirects-validate --fix to flatten.", $source));
            }
            if ($this->redirects->servesPublishedContent($source)) {
                $result->add(new SeoIssue('redirect-shadows-live-page', SeoIssueSeverity::Warning, 'Source path also serves a published page; the page wins and the redirect is unused.', $source));
            }
            if (! $chain['gone'] && $chain['destination'] !== null && ! $this->resolves($chain['destination'])) {
                $result->add(new SeoIssue('redirect-dead-target', SeoIssueSeverity::Warning, "Destination {$chain['destination']} does not serve a page.", $source));
            }
            if ($redirect->status_code !== 301 && $redirect->status_code !== 308) {
                $result->add(new SeoIssue('redirect-temporary', SeoIssueSeverity::Information, 'Temporary redirect: confirm it is still intended.', $source));
            }
        });

        return $result;
    }

    /** Rewrites every multi-hop redirect to point at its final destination. */
    public function flatten(): int
    {
        $fixed = 0;
        DB::transaction(function () use (&$fixed): void {
            Redirect::query()->where('enabled', true)->where('status_code', '!=', 410)->get()->each(function (Redirect $redirect) use (&$fixed): void {
                $chain = $this->resolver->finalDestination($redirect->source_path);
                if ($chain === null || $chain['hops'] <= 1) {
                    return;
                }
                $redirect->forceFill($chain['gone']
                    ? ['destination_url' => null, 'status_code' => 410]
                    : ['destination_url' => $chain['destination']])->save();
                $fixed++;
            });
        });

        return $fixed;
    }

    private function resolves(string $path): bool
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $type = isset($segments[0]) ? PublicResourceType::fromSegment($segments[0]) : null;
        if ($type !== null && count($segments) === 2) {
            return app(PublicResourceQuery::class)->resolve($type, $segments[1])['state'] === 'current';
        }

        return $this->resolver->servesContent($path);
    }
}
