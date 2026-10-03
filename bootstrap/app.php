<?php

declare(strict_types=1);

use App\Exceptions\ContentVersionConflictException;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\SettingsVersionConflictException;
use App\Foundation\Application;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\AssignCorrelationId;
use App\Http\Middleware\EnforceHttps;
use App\Http\Middleware\EnsureMfaVerified;
use App\Http\Middleware\EnsureOperationalSettingEnabled;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureRecentMfa;
use App\Http\Middleware\EnsureSessionIsCurrent;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts(
            at: static fn (): array => config('impact.security.trusted_hosts', []),
            subdomains: false,
        );
        $middleware->web(prepend: [
            EnforceHttps::class,
            AddSecurityHeaders::class,
            AssignCorrelationId::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'mfa' => EnsureMfaVerified::class,
            'setting.enabled' => EnsureOperationalSettingEnabled::class,
            'permission' => EnsurePermission::class,
            'recent_mfa' => EnsureRecentMfa::class,
            'session.current' => EnsureSessionIsCurrent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();
            if (! in_array($status, [403, 404, 419, 429, 500, 503], true)
                || ($request->expectsJson() && ! $request->header('X-Inertia'))
                || ($status === 500 && config('app.debug'))) {
                return $response;
            }

            return Inertia::render('Error', [
                'status' => $status,
                'meta' => ['robots' => 'noindex,nofollow'],
                'correlation_id' => $request->attributes->get('correlation_id'),
            ])->toResponse($request)->setStatusCode($status);
        });
        $exceptions->render(function (
            ContentVersionConflictException $exception,
            Request $request,
        ): Response {
            $payload = [
                'code' => 'CONTENT_VERSION_CONFLICT',
                'message' => __('This content changed after you opened it. Reload and merge your changes.'),
                'correlation_id' => $request->attributes->get('correlation_id'),
                'current_version_id' => $exception->currentVersionId,
            ];

            return $request->expectsJson() && ! $request->header('X-Inertia')
                ? response()->json($payload, 409)
                : Inertia::render('Error', ['status' => 409, ...$payload, 'meta' => ['robots' => 'noindex,nofollow']])->toResponse($request)->setStatusCode(409);
        });
        $exceptions->render(function (
            InvalidStateTransitionException $exception,
            Request $request,
        ): Response {
            $payload = [
                'code' => 'INVALID_STATE_TRANSITION',
                'message' => __('This request conflicts with the current state.'),
                'correlation_id' => $request->attributes->get('correlation_id'),
            ];

            return $request->expectsJson() && ! $request->header('X-Inertia')
                ? response()->json($payload, 409)
                : Inertia::render('Error', ['status' => 409, ...$payload, 'meta' => ['robots' => 'noindex,nofollow']])->toResponse($request)->setStatusCode(409);
        });
        $exceptions->render(function (
            SettingsVersionConflictException $exception,
            Request $request,
        ): Response {
            $payload = [
                'code' => 'SETTINGS_VERSION_CONFLICT',
                'message' => __('These settings changed after you opened them. Reload the category, compare the current values, and submit again.'),
                'correlation_id' => $request->attributes->get('correlation_id'),
                'category' => $exception->category,
            ];

            return $request->expectsJson() && ! $request->header('X-Inertia')
                ? response()->json($payload, 409)
                : Inertia::render('Error', ['status' => 409, ...$payload, 'meta' => ['robots' => 'noindex,nofollow']])->toResponse($request)->setStatusCode(409);
        });
    })
    ->create();
