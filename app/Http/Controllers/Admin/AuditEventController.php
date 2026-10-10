<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\User;
use App\Support\AuditEventPresenter;
use App\Support\Inertia\WorkspacePage;
use Illuminate\Http\Request;
use Inertia\Response as View;

final class AuditEventController extends Controller
{
    /** Areas a reader filters by, as audit action prefixes. */
    private const AREAS = [
        'website_content' => 'Website listings',
        'page_composition' => 'Website pages',
        'content' => 'Content drafts',
        'engagement' => 'Requests',
        'application' => 'Applications',
        'media' => 'Media',
        'navigation' => 'Menus and footer',
        'seo' => 'SEO',
        'identity' => 'Team and access',
        'settings' => 'Settings',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:120'],
            'actor' => ['nullable', 'uuid'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $events = AuditEvent::query()
            ->with('actor:id,name')
            ->when(filled($filters['action'] ?? null), fn ($query) => $query
                ->where('action', 'like', $filters['action'].'%'))
            ->when(filled($filters['actor'] ?? null), fn ($query) => $query->where('actor_id', $filters['actor']))
            ->when(filled($filters['from'] ?? null), fn ($query) => $query
                ->whereDate('created_at', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn ($query) => $query
                ->whereDate('created_at', '<=', $filters['to']))
            ->latest()
            ->paginate(50)
            ->withQueryString()
            ->through(AuditEventPresenter::present(...));

        return WorkspacePage::render('admin.audit.index', [
            'events' => $events,
            'areas' => collect(self::AREAS)->map(fn (string $label, string $value): array => ['value' => $value.'.', 'label' => $label])->values()->all(),
            'people' => User::query()->orderBy('name')->get(['id', 'name'])->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => (string) $user->name])->all(),
            'filters' => [
                'action' => $filters['action'] ?? '',
                'actor' => $filters['actor'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
        ]);
    }
}
