<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Support\SettingCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SettingHistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $category = $request->string('category')->toString();
        $setting = $request->string('setting')->toString();
        $actor = $request->string('actor')->toString();

        $events = AuditEvent::query()
            ->with('actor:id,name,email')
            ->whereIn('action', ['settings.updated', 'settings.reset'])
            ->when($category !== '', fn ($query) => $query->where('metadata->category', $category))
            ->when($setting !== '', fn ($query) => $query->where('metadata->key', $setting))
            ->when($actor !== '', fn ($query) => $query->whereHas(
                'actor',
                fn ($actorQuery) => $actorQuery
                    ->where('name', 'like', '%'.$actor.'%')
                    ->orWhere('email', 'like', '%'.$actor.'%')
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.settings.history', [
            'categories' => SettingCatalog::CATEGORIES,
            'events' => $events,
            'filters' => [
                'actor' => $actor,
                'category' => $category,
                'setting' => $setting,
            ],
            'navigationGroups' => SettingCatalog::navigationGroups(),
            'settings' => SettingCatalog::DEFINITIONS,
        ]);
    }
}
