<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Seo\SaveRedirectAction;
use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Enums\Seo\RedirectOrigin;
use App\Http\Controllers\Controller;
use App\Jobs\Seo\GenerateSitemapsJob;
use App\Models\Redirect;
use App\Services\Seo\RedirectAuditor;
use App\Support\CorrelationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SeoRedirectController extends Controller
{
    public function store(Request $request, SaveRedirectAction $redirects): RedirectResponse
    {
        $validated = $this->validated($request);
        $redirects->execute(
            source: $validated['source_path'],
            destination: $validated['destination_url'] ?? null,
            status: (int) $validated['status_code'],
            origin: RedirectOrigin::Manual,
            reason: $validated['reason'],
            actorId: (string) $request->user()?->getKey(),
        );

        return back()->with('status', __('Redirect saved.'));
    }

    public function update(Request $request, Redirect $redirect, SaveRedirectAction $redirects): RedirectResponse
    {
        $validated = $this->validated($request);
        $redirects->execute(
            source: $validated['source_path'],
            destination: $validated['destination_url'] ?? null,
            status: (int) $validated['status_code'],
            origin: $redirect->origin ?? RedirectOrigin::Manual,
            reason: $validated['reason'],
            actorId: (string) $request->user()?->getKey(),
            existing: $redirect,
        );

        return back()->with('status', __('Redirect updated.'));
    }

    /** Disables rather than deletes, so redirect history is preserved. */
    public function destroy(Request $request, Redirect $redirect, AuditRecorder $audit, CorrelationContext $correlation): RedirectResponse
    {
        $redirect->forceFill(['enabled' => false])->save();
        $audit->record(new AuditData(
            action: 'seo.redirect.disabled',
            auditableType: Redirect::class,
            auditableId: (string) $redirect->getKey(),
            actorId: (string) $request->user()?->getKey(),
            correlationId: $correlation->id(),
            metadata: ['source' => $redirect->source_path],
        ));
        GenerateSitemapsJob::dispatch();

        return back()->with('status', __('Redirect disabled.'));
    }

    public function flatten(RedirectAuditor $auditor): RedirectResponse
    {
        return back()->with('status', __(':count redirect chain(s) flattened.', ['count' => $auditor->flatten()]));
    }

    /** @return array{source_path: string, destination_url?: string|null, status_code: int|string, reason: string} */
    private function validated(Request $request): array
    {
        return $request->validate([
            'source_path' => ['required', 'string', 'max:768', 'starts_with:/', 'not_regex:/[?#\s]/'],
            'destination_url' => ['nullable', 'required_unless:status_code,410', 'string', 'max:1024'],
            'status_code' => ['required', 'in:301,302,307,308,410'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
    }
}
