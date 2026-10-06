<?php

declare(strict_types=1);

namespace App\Services\Seo;

use Illuminate\Http\Request;

/**
 * The one place absolute public URLs are produced.
 *
 * Canonical form: configured scheme and host, lowercase path, no trailing
 * slash (except the root), no session or tracking parameters, and only the
 * query parameters a page explicitly declares as meaningful.
 */
final readonly class CanonicalUrlBuilder
{
    public function __construct(private SeoSettings $settings) {}

    public function normalizePath(string $path): string
    {
        $path = (string) parse_url('/'.ltrim($path, '/'), PHP_URL_PATH);
        $path = preg_replace('#/{2,}#', '/', $path) ?? '/';
        $path = strtolower(rawurldecode($path));
        $path = $path === '/' ? '/' : rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    /** @param  array<string, scalar|null>  $query */
    public function url(string $path, array $query = []): string
    {
        $query = array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== '');
        ksort($query);
        $suffix = $query === [] ? '' : '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $path = $this->normalizePath($path);

        return $this->settings->canonicalBaseUrl().($path === '/' ? '/' : $path).$suffix;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, scalar|null>  $query
     */
    public function forRoute(string $name, array $parameters = [], array $query = []): string
    {
        return $this->url(route($name, $parameters, false), $query);
    }

    /**
     * Canonical URL of the current request. Pagination is the only query
     * state kept by default: page 2+ of a real listing is distinct content,
     * while filters, sorting and tracking parameters are not.
     *
     * @param  list<string>  $meaningfulQuery
     */
    public function forRequest(Request $request, array $meaningfulQuery = ['page']): string
    {
        $query = [];
        foreach ($meaningfulQuery as $key) {
            $value = $request->query($key);
            if (! is_scalar($value) || $value === '') {
                continue;
            }
            if ($key === 'page' && (int) $value <= 1) {
                continue;
            }
            $query[$key] = $key === 'page' ? (int) $value : (string) $value;
        }

        return $this->url($request->getPathInfo(), $query);
    }

    /** Converts any local absolute URL or path to an absolute canonical URL. */
    public function absolute(string $pathOrUrl): string
    {
        if (preg_match('#^https?://#i', $pathOrUrl) === 1) {
            $host = strtolower((string) parse_url($pathOrUrl, PHP_URL_HOST));
            if ($host !== $this->settings->canonicalHost() && $host !== strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST))) {
                return $pathOrUrl;
            }
            $path = (string) parse_url($pathOrUrl, PHP_URL_PATH);
            $query = (string) parse_url($pathOrUrl, PHP_URL_QUERY);

            return $this->settings->canonicalBaseUrl().'/'.ltrim($path, '/').($query !== '' ? '?'.$query : '');
        }

        return $this->settings->canonicalBaseUrl().'/'.ltrim($pathOrUrl, '/');
    }

    /**
     * Canonical overrides may only point at this site (or an approved host
     * from SEO_ALLOWED_CANONICAL_HOSTS). Returns null when unsafe.
     */
    public function safeOverride(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return $this->url($value);
        }

        $parts = parse_url($value);
        if (! is_array($parts) || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['https', 'http'], true)) {
            return null;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === $this->settings->canonicalHost()) {
            return $this->url((string) ($parts['path'] ?? '/'));
        }
        if (in_array($host, (array) config('impact.seo.allowed_canonical_hosts', []), true)
            && strtolower((string) $parts['scheme']) === 'https') {
            return $value;
        }

        return null;
    }
}
