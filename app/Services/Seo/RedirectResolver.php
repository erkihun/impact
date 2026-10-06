<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Data\Seo\RedirectResolution;
use App\Enums\Seo\PublicResourceType;
use App\Models\Redirect;
use App\Queries\Seo\PublicResourceQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Resolves managed redirects, retired-language URLs and moved slugs to a
 * single final destination so visitors and crawlers never follow a chain.
 */
final readonly class RedirectResolver
{
    public const MAX_HOPS = 10;

    public function __construct(
        private CanonicalUrlBuilder $urls,
        private PublicResourceQuery $resources,
        private Router $router,
    ) {}

    /** Managed redirect for a path, followed to its final local destination. */
    public function resolve(string $path): ?RedirectResolution
    {
        $path = $this->urls->normalizePath($path);
        $redirect = $this->find($path);
        if ($redirect === null) {
            return null;
        }
        if ($redirect->isGone()) {
            return new RedirectResolution(null, 410, (string) $redirect->getKey());
        }

        $final = $this->finalDestination($path);
        if ($final === null) {
            return null;
        }

        return new RedirectResolution(
            $final['destination'],
            $final['gone'] ? 410 : (in_array($redirect->status_code, [301, 302, 307, 308], true) ? $redirect->status_code : 301),
            (string) $redirect->getKey(),
        );
    }

    /**
     * Follows a chain starting at $path. Returns null on a loop or an unsafe
     * hop; "gone" when the chain ends in a 410 record.
     *
     * @return array{destination: string|null, gone: bool, hops: int}|null
     */
    public function finalDestination(string $path): ?array
    {
        $visited = [$this->urls->normalizePath($path)];
        $current = $visited[0];
        for ($hop = 0; $hop < self::MAX_HOPS; $hop++) {
            $redirect = $this->find($current);
            if ($redirect === null) {
                return $hop === 0 ? null : ['destination' => $current, 'gone' => false, 'hops' => $hop];
            }
            if ($redirect->isGone()) {
                return ['destination' => null, 'gone' => true, 'hops' => $hop + 1];
            }
            $next = $this->localPath($redirect->destination_url);
            if ($next === null || in_array($next, $visited, true)) {
                return null;
            }
            $visited[] = $next;
            $current = $next;
        }

        return null;
    }

    /**
     * Retired /en/... and /am/... URLs. English paths move to the unprefixed
     * equivalent; other languages move only when the same resource has a
     * published English page, and are otherwise Gone.
     */
    public function resolveLegacy(string $locale, string $path): RedirectResolution
    {
        $legacyPath = $this->urls->normalizePath("/{$locale}/{$path}");
        $managed = $this->resolve($legacyPath);
        if ($managed !== null) {
            return $managed;
        }

        $candidate = $this->urls->normalizePath('/'.$path);
        $managed = $this->resolve($candidate);
        if ($managed !== null) {
            return $managed;
        }

        $segments = array_values(array_filter(explode('/', trim($candidate, '/'))));
        $type = isset($segments[0]) ? PublicResourceType::fromSegment($segments[0]) : null;
        if ($type !== null && count($segments) === 2) {
            $resolved = $this->resources->resolve($type, $segments[1]);

            return match ($resolved['state']) {
                'current', 'moved' => new RedirectResolution(route($type->showRoute(), ['slug' => $resolved['record']?->getAttribute('slug')], false), 301),
                'gone' => new RedirectResolution(null, 410),
                default => $locale === 'en' ? new RedirectResolution(null, 404) : new RedirectResolution(null, 410),
            };
        }

        if ($this->servesContent($candidate)) {
            return new RedirectResolution($candidate, 301);
        }

        return new RedirectResolution(null, $locale === 'en' ? 404 : 410);
    }

    /** True when a non-fallback GET route answers the path. */
    public function servesContent(string $path): bool
    {
        try {
            $route = $this->router->getRoutes()->match(Request::create($path, 'GET'));
        } catch (HttpException) {
            return false;
        }

        return ! $route->isFallback
            && ! str_starts_with((string) $route->getName(), 'legacy.');
    }

    /** Converts a stored destination to a safe local path, or null. */
    public function localPath(?string $destination): ?string
    {
        $destination = trim((string) $destination);
        if ($destination === '') {
            return null;
        }
        if (str_starts_with($destination, '/') && ! str_starts_with($destination, '//')) {
            return $this->urls->normalizePath($destination);
        }
        $host = strtolower((string) parse_url($destination, PHP_URL_HOST));
        $local = [
            strtolower((string) parse_url($this->urls->url('/'), PHP_URL_HOST)),
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
        ];

        return $host !== '' && in_array($host, $local, true)
            ? $this->urls->normalizePath((string) parse_url($destination, PHP_URL_PATH))
            : null;
    }

    private function find(string $path): ?Redirect
    {
        return Redirect::query()->where('source_path', $path)->where('enabled', true)->first();
    }
}
