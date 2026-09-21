<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Exceptions\ContentVersionConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNavigationRequest;
use App\Models\PageNavigationConfiguration;
use App\Support\CorrelationContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

final class NavigationController extends Controller
{
    public function edit(): View
    {
        abort_unless(request()->user()?->hasPermission('navigation.manage'), 403);

        return view('admin.navigation.edit', [
            'items' => PageNavigationConfiguration::query()
                ->orderBy('locale')
                ->orderBy('location')
                ->orderBy('sort_order')
                ->get()
                ->groupBy(fn ($item): string => "{$item->locale}:{$item->location}"),
        ]);
    }

    public function update(
        UpdateNavigationRequest $request,
        AuditRecorder $audit,
        CorrelationContext $correlation,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $audit, $correlation): void {
            foreach ($request->validated('items') as $data) {
                $item = PageNavigationConfiguration::query()->lockForUpdate()->findOrFail($data['id']);
                if ($item->lock_version !== (int) $data['lock_version']) {
                    throw new ContentVersionConflictException(
                        (string) $data['lock_version'],
                        (string) $item->lock_version,
                    );
                }
                abort_unless(Route::has($item->route_name), 422);
                $before = hash('sha256', json_encode($item->only([
                    'label', 'description', 'sort_order', 'enabled',
                ]), JSON_THROW_ON_ERROR));
                $item->forceFill([
                    'label' => $data['label'],
                    'description' => $data['description'] ?? null,
                    'sort_order' => (int) $data['sort_order'],
                    'enabled' => (bool) ($data['enabled'] ?? false),
                    'version_no' => $item->version_no + 1,
                    'lock_version' => $item->lock_version + 1,
                    'updated_by' => $request->user()->getKey(),
                    'published_at' => now('UTC'),
                ])->save();
                $after = hash('sha256', json_encode($item->only([
                    'label', 'description', 'sort_order', 'enabled',
                ]), JSON_THROW_ON_ERROR));
                $audit->record(new AuditData(
                    action: 'navigation.updated',
                    auditableType: PageNavigationConfiguration::class,
                    auditableId: (string) $item->getKey(),
                    actorId: (string) $request->user()->getKey(),
                    correlationId: $correlation->id(),
                    beforeHash: $before,
                    afterHash: $after,
                    metadata: ['location' => $item->location, 'locale' => $item->locale],
                ));
                Cache::forget("public-navigation:{$item->location}:{$item->locale}");
            }
        }, attempts: 3);

        return back()->with('status', __('Navigation and footer configuration updated.'));
    }
}
