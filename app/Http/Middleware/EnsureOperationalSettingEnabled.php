<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\SettingCatalog;
use App\Support\Settings\EffectiveSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureOperationalSettingEnabled
{
    public function __construct(private EffectiveSettings $settings) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $key): Response
    {
        abort_unless(
            isset(SettingCatalog::DEFINITIONS[$key])
                && SettingCatalog::DEFINITIONS[$key]['type'] === 'boolean'
                && $this->settings->boolean($key),
            404,
        );

        return $next($request);
    }
}
