<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class AuditEventController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $events = AuditEvent::query()
            ->with('actor:id,name')
            ->when(filled($filters['action'] ?? null), fn ($query) => $query
                ->where('action', 'like', $filters['action'].'%'))
            ->when(filled($filters['from'] ?? null), fn ($query) => $query
                ->whereDate('created_at', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn ($query) => $query
                ->whereDate('created_at', '<=', $filters['to']))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit.index', compact('events'));
    }
}
