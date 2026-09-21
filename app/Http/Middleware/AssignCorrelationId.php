<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CorrelationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final readonly class AssignCorrelationId
{
    public function __construct(private CorrelationContext $context) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get('X-Request-ID');
        $id = is_string($incoming) && Str::isUuid($incoming)
            ? strtolower($incoming)
            : (string) Str::uuid7();

        $this->context->replace($id);
        $request->attributes->set('correlation_id', $id);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $id);

        return $response;
    }
}
