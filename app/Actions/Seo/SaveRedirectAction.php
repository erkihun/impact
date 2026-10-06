<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Enums\Seo\PublicResourceType;
use App\Enums\Seo\RedirectOrigin;
use App\Jobs\Seo\GenerateSitemapsJob;
use App\Models\Redirect;
use App\Queries\Seo\PublicResourceQuery;
use App\Services\Seo\CanonicalUrlBuilder;
use App\Services\Seo\RedirectResolver;
use App\Support\CorrelationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates or updates a governed redirect.
 *
 * Guarantees: local destinations only, no loops, no chains (A→B plus B→C is
 * stored as A→C and B→C), no duplicate sources, and no redirect that would
 * hide a page that is currently published.
 */
final readonly class SaveRedirectAction
{
    /** Paths that must never be redirected away. */
    private const PROTECTED_PREFIXES = ['/admin', '/login', '/logout', '/mfa', '/register', '/forgot-password', '/reset-password', '/sitemap', '/sitemaps', '/robots.txt', '/up', '/ready', '/api', '/storage', '/build'];

    public function __construct(
        private CanonicalUrlBuilder $urls,
        private RedirectResolver $resolver,
        private PublicResourceQuery $resources,
        private AuditRecorder $audit,
        private CorrelationContext $correlation,
    ) {}

    public function execute(
        string $source,
        ?string $destination,
        int $status = 301,
        RedirectOrigin $origin = RedirectOrigin::Manual,
        ?string $reason = null,
        ?string $actorId = null,
        ?Redirect $existing = null,
        ?string $subjectType = null,
        ?string $subjectKey = null,
        bool $allowActiveSource = false,
    ): Redirect {
        $source = $this->urls->normalizePath($source);
        $errors = [];

        if (! in_array($status, [301, 302, 307, 308, 410], true)) {
            $errors['status_code'] = __('Choose 301, 302, 307, 308 or 410.');
        }
        if ($source === '/' || $this->isProtected($source)) {
            $errors['source_path'] = __('This path cannot be redirected.');
        }
        if (! $allowActiveSource && $this->servesPublishedContent($source)) {
            $errors['source_path'] = __('This URL currently serves a published page. Unpublish or change the page first.');
        }
        $duplicate = Redirect::query()->where('source_path', $source)
            ->when($existing !== null, fn ($query) => $query->whereKeyNot($existing->getKey()))
            ->first();
        if ($duplicate !== null && $existing === null && $origin === RedirectOrigin::Manual) {
            $errors['source_path'] = __('A redirect for this path already exists.');
        }

        $final = null;
        if ($status !== 410) {
            $target = $this->resolver->localPath($destination);
            if ($target === null) {
                $errors['destination_url'] = __('Use a path on this website, for example /services/strategy.');
            } elseif ($target === $source) {
                $errors['destination_url'] = __('A redirect cannot point to itself.');
            } else {
                $chain = $this->resolver->finalDestination($target);
                if ($chain !== null && ($chain['destination'] === $source || $chain['gone'])) {
                    $errors['destination_url'] = $chain['gone']
                        ? __('The destination is marked as gone.')
                        : __('This redirect would create a loop.');
                } elseif ($chain === null && Redirect::query()->where('source_path', $target)->exists()) {
                    $errors['destination_url'] = __('This redirect would create a loop.');
                } else {
                    $final = $chain['destination'] ?? $target;
                    if ($final === $source) {
                        $errors['destination_url'] = __('This redirect would create a loop.');
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($source, $final, $status, $origin, $reason, $actorId, $existing, $duplicate, $subjectType, $subjectKey): Redirect {
            $redirect = $existing ?? $duplicate ?? new Redirect;
            $before = $redirect->exists ? $redirect->only(['source_path', 'destination_url', 'status_code']) : null;
            $redirect->forceFill([
                'source_path' => $source,
                'destination_url' => $status === 410 ? null : $final,
                'status_code' => $status,
                'enabled' => true,
                'origin' => $origin,
                'reason' => $reason,
                'subject_type' => $subjectType ?? $redirect->subject_type,
                'subject_key' => $subjectKey ?? $redirect->subject_key,
                'created_by' => $redirect->created_by ?? $actorId,
            ])->save();

            // Flatten chains: anything that pointed at the old source now
            // points directly at the final destination (or becomes Gone).
            Redirect::query()
                ->where('destination_url', $source)
                ->whereKeyNot($redirect->getKey())
                ->get()
                ->each(fn (Redirect $upstream) => $upstream->forceFill($status === 410
                    ? ['destination_url' => null, 'status_code' => 410]
                    : ['destination_url' => $final])->save());

            $this->audit->record(new AuditData(
                action: $before === null ? 'seo.redirect.created' : 'seo.redirect.updated',
                auditableType: Redirect::class,
                auditableId: (string) $redirect->getKey(),
                actorId: $actorId,
                correlationId: $this->correlation->id(),
                beforeHash: $before === null ? null : hash('sha256', (string) json_encode($before)),
                afterHash: hash('sha256', (string) json_encode($redirect->only(['source_path', 'destination_url', 'status_code']))),
                metadata: ['origin' => $origin->value, 'status' => $status],
            ));

            DB::afterCommit(static fn () => GenerateSitemapsJob::dispatch());

            return $redirect;
        });
    }

    public function servesPublishedContent(string $path): bool
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $type = isset($segments[0]) ? PublicResourceType::fromSegment($segments[0]) : null;
        if ($type !== null && count($segments) === 2) {
            return $this->resources->resolve($type, $segments[1])['state'] === 'current';
        }
        if (in_array($segments[0] ?? '', ['en', 'am'], true)) {
            return false;
        }

        return $this->resolver->servesContent($path);
    }

    private function isProtected(string $path): bool
    {
        foreach (self::PROTECTED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/').'/')) {
                return true;
            }
        }

        return false;
    }
}
