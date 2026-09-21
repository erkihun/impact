<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Http\Controllers\Controller;
use App\Support\CorrelationContext;
use App\Support\SettingCatalog;
use App\Support\Settings\SettingsVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class SettingsDiagnosticsController extends Controller
{
    public function __invoke(
        Request $request,
        SettingsVerificationService $verification,
        AuditRecorder $audit,
        CorrelationContext $correlation,
    ): View {
        $report = Cache::get('settings.verification.last');
        if (! is_array($report)) {
            $report = $verification->verify();
        }

        $audit->record(new AuditData(
            action: 'settings.diagnostics.viewed',
            auditableType: self::class,
            auditableId: null,
            actorId: (string) $request->user()->getKey(),
            correlationId: $correlation->id(),
            metadata: ['error_count' => $report['summary']['error_count']],
        ));

        return view('admin.settings.diagnostics', [
            'navigationGroups' => SettingCatalog::navigationGroups(),
            'report' => $report,
        ]);
    }

    public function run(
        Request $request,
        SettingsVerificationService $verification,
        AuditRecorder $audit,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $report = $verification->verify();
        Cache::put('settings.verification.last', $report, now()->addDay());

        $audit->record(new AuditData(
            action: 'settings.diagnostics.executed',
            auditableType: self::class,
            auditableId: null,
            actorId: (string) $request->user()->getKey(),
            correlationId: $correlation->id(),
            metadata: [
                'error_count' => $report['summary']['error_count'],
                'warning_count' => $report['summary']['warning_count'],
            ],
        ));

        return back()->with('status', __('Settings verification completed.'));
    }
}
