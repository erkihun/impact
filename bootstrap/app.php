<?php

declare(strict_types=1);

use App\Exceptions\ContentVersionConflictException;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\SettingsVersionConflictException;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\AssignCorrelationId;
use App\Http\Middleware\EnforceHttps;
use App\Http\Middleware\EnsureMfaVerified;
use App\Http\Middleware\EnsureOperationalSettingEnabled;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureRecentMfa;
use App\Http\Middleware\EnsureSessionIsCurrent;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
            SetLocale::class,
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

            return $request->expectsJson()
                ? response()->json($payload, 409)
                : response()->view('errors.409', $payload, 409);
        });
        $exceptions->render(function (
            InvalidStateTransitionException $exception,
            Request $request,
        ): Response {
            $payload = [
                'code' => 'INVALID_STATE_TRANSITION',
                'message' => __('The requested workflow transition is not allowed.'),
                'correlation_id' => $request->attributes->get('correlation_id'),
            ];

            return $request->expectsJson()
                ? response()->json($payload, 409)
                : response()->view('errors.409', $payload, 409);
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

            return $request->expectsJson()
                ? response()->json($payload, 409)
                : response()->view('errors.409', $payload, 409);
        });
    })
    ->create();
