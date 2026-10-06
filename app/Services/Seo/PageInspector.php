<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\CorrelationContext;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Inertia\ResponseFactory;
use Throwable;

/**
 * Renders a public URL through the HTTP kernel, exactly as a crawler would
 * receive it, and extracts what the audit needs: status, robots signals,
 * head elements, structured data and the page's <h1> headings.
 */
final class PageInspector
{
    /** Section types that render an <h1> in PageComposition.jsx. */
    private const HEADING_SECTIONS = ['page_header', 'form_introduction', 'homepage_hero'];

    private ?string $version = null;

    public function __construct(
        private readonly Kernel $kernel,
        private readonly SeoSettings $settings,
    ) {}

    /**
     * @return array{
     *     path: string, status: int, location: string|null, robotsHeader: string|null,
     *     component: string|null, seo: array<string, mixed>, head: list<string>,
     *     jsonLd: list<string>, h1: list<string>, props: array<string, mixed>
     * }
     */
    public function inspect(string $path): array
    {
        $request = Request::create($this->settings->canonicalBaseUrl().$path, 'GET', server: [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_VERSION' => $this->version(),
            'HTTP_ACCEPT' => 'text/html, application/xhtml+xml',
            'HTTP_USER_AGENT' => 'ImpactSeoAudit/1.0',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        try {
            $response = $this->isolated(function () use ($request) {
                $response = $this->kernel->handle($request);
                $this->kernel->terminate($request, $response);

                return $response;
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->empty($path, 500);
        }

        $status = $response->getStatusCode();
        $payload = json_decode((string) $response->getContent(), true);
        $props = is_array($payload) ? (array) ($payload['props'] ?? []) : [];
        $seo = (array) ($props['seo'] ?? []);
        $head = array_values(array_filter((array) ($seo['head'] ?? []), 'is_string'));

        return [
            'path' => $path,
            'status' => $status,
            'location' => $response->headers->get('Location'),
            'robotsHeader' => $response->headers->get('X-Robots-Tag'),
            'component' => is_array($payload) ? ($payload['component'] ?? null) : null,
            'seo' => $seo,
            'head' => $head,
            'jsonLd' => collect($head)
                ->map(static fn (string $element): ?string => preg_match('#<script type="application/ld\+json"[^>]*>(.*)</script>#s', $element, $match) === 1 ? $match[1] : null)
                ->filter()
                ->values()
                ->all(),
            'h1' => $this->headings((string) ($payload['component'] ?? ''), $props),
            'props' => $props,
        ];
    }

    /** Full HTML response for a URL (first visit, as crawlers fetch it). */
    public function html(string $path): string
    {
        $request = Request::create($this->settings->canonicalBaseUrl().$path, 'GET', server: ['REMOTE_ADDR' => '127.0.0.1']);
        try {
            return $this->isolated(function () use ($request): string {
                $response = $this->kernel->handle($request);
                $this->kernel->terminate($request, $response);

                return (string) $response->getContent();
            });
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Runs a sub-request without leaking its state into the surrounding
     * request (an admin request that triggers an audit or preview): the
     * bound request, the session, Inertia shared props and the correlation
     * id are restored afterwards.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function isolated(callable $callback): mixed
    {
        $app = app();
        $outerRequest = $app->bound('request') ? $app->make('request') : null;
        $session = $app->resolved('session.store') ? $app->make('session.store') : null;
        $sessionState = $session?->isStarted() ? [$session->getId(), $session->all()] : null;
        $inertia = $app->make(ResponseFactory::class);
        $shared = $inertia->getShared();
        $correlation = $app->make(CorrelationContext::class)->id();
        $guard = $app->make('auth')->guard();
        $user = $guard->hasUser() ? $guard->user() : null;

        try {
            return $callback();
        } finally {
            if ($outerRequest instanceof Request) {
                $app->instance('request', $outerRequest);
                Facade::clearResolvedInstance('request');
            }
            if ($session !== null && $sessionState !== null) {
                $session->setId($sessionState[0]);
                $session->flush();
                $session->put($sessionState[1]);
            }
            $inertia->flushShared();
            $inertia->share($shared);
            $app->make(CorrelationContext::class)->replace($correlation);
            if ($user !== null) {
                $guard->setUser($user);
            }
        }
    }

    /**
     * Mirrors PageIntro/HeroSlider: composition heading sections render
     * <h1>; otherwise the built-in page header does; the homepage hero
     * renders the first slide heading as <h1>.
     *
     * @param  array<string, mixed>  $props
     * @return list<string>
     */
    private function headings(string $component, array $props): array
    {
        if ($component === 'Public/Home') {
            $slide = $props['heroSlider']['slides'][0]['heading'] ?? null;

            return $slide === null ? [] : [trim((string) $slide)];
        }

        $sections = (array) ($props['composition']['sections'] ?? []);
        $headings = [];
        foreach ($sections as $section) {
            if (in_array($section['type'] ?? null, self::HEADING_SECTIONS, true)) {
                $headings[] = trim((string) ($section['content']['heading'] ?? ''));
            }
        }
        if ($headings === [] && isset($props['header']['title'])) {
            $headings[] = trim((string) $props['header']['title']);
        }

        return $headings;
    }

    private function version(): string
    {
        return $this->version ??= (string) (app(HandleInertiaRequests::class)->version(Request::create('/')) ?? '');
    }

    /** @return array{path: string, status: int, location: null, robotsHeader: null, component: null, seo: array<string, mixed>, head: list<string>, jsonLd: list<string>, h1: list<string>, props: array<string, mixed>} */
    private function empty(string $path, int $status): array
    {
        return ['path' => $path, 'status' => $status, 'location' => null, 'robotsHeader' => null, 'component' => null, 'seo' => [], 'head' => [], 'jsonLd' => [], 'h1' => [], 'props' => []];
    }
}
