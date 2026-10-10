<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Engagement\UpdateSubmissionAction;
use App\Data\Engagement\UpdateSubmissionData;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSubmissionRequest;
use App\Models\EngagementSubmission;
use App\Models\User;
use App\Support\CorrelationContext;
use App\Support\Inertia\WorkspacePage;
use App\Support\Inertia\WorkspaceShell;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Response as View;

/**
 * Requests inbox: the list of one status group beside the open request.
 */
final class EngagementSubmissionController extends Controller
{
    /** @var array<string, list<string>> inbox tabs as groups of statuses */
    private const VIEWS = [
        'new' => WorkspaceShell::NEW_REQUEST_STATUSES,
        'progress' => ['assigned', 'in_progress', 'contacted', 'qualified'],
        'waiting' => ['awaiting_client'],
        'closed' => ['resolved', 'not_proceeding', 'closed', 'rejected'],
    ];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EngagementSubmission::class);

        return WorkspacePage::render('admin.engagement.index', $this->inbox($request, 'new'));
    }

    public function show(
        Request $request,
        EngagementSubmission $submission,
        UpdateSubmissionAction $action,
    ): View {
        Gate::authorize('view', $submission);
        $submission->load(['history.actor:id,name', 'assignee', 'service', 'files.mediaAsset']);
        $status = (string) $submission->getRawOriginal('status');

        return WorkspacePage::render('admin.engagement.show', [
            ...$this->inbox($request, $this->viewFor($status)),
            'submission' => $submission,
            'assignees' => User::query()->where('status', 'active')->orderBy('name')->get(),
            'statuses' => $action->allowedDestinations(SubmissionStatus::from($status)),
        ]);
    }

    public function update(
        UpdateSubmissionRequest $request,
        EngagementSubmission $submission,
        UpdateSubmissionAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validated();
        $action->execute($request->user(), new UpdateSubmissionData(
            submissionId: (string) $submission->id,
            actorId: (string) $request->user()->getKey(),
            status: SubmissionStatus::from($validated['status']),
            correlationId: $correlation->id(),
            assignedTo: $validated['assigned_to'] ?? null,
            note: $validated['note'] ?? null,
        ));

        return back()->with('status', __('Request updated.'));
    }

    /** @return array<string, mixed> the tab list, its counts and the current filters */
    private function inbox(Request $request, string $defaultView): array
    {
        $view = array_key_exists((string) $request->query('view'), self::VIEWS) ? (string) $request->query('view') : $defaultView;
        $type = in_array($request->query('type'), array_column(SubmissionType::cases(), 'value'), true) ? (string) $request->query('type') : '';
        $assigned = in_array($request->query('assigned'), ['me', 'none'], true) ? (string) $request->query('assigned') : '';
        $filtered = fn ($query) => $query
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($assigned === 'me', fn ($query) => $query->where('assigned_to', $request->user()?->getKey()))
            ->when($assigned === 'none', fn ($query) => $query->whereNull('assigned_to'));

        $counts = [];
        foreach (self::VIEWS as $key => $statuses) {
            $counts[$key] = $filtered(EngagementSubmission::query()->whereIn('status', $statuses))->count();
        }

        return [
            'submissions' => $filtered(EngagementSubmission::query()->with('assignee:id,name')->whereIn('status', self::VIEWS[$view]))
                ->latest('submitted_at')
                ->paginate(30, ['id', 'reference_no', 'type', 'status', 'organization_name', 'contact_name', 'assigned_to', 'submitted_at'])
                ->withQueryString(),
            'counts' => $counts,
            'filters' => ['view' => $view, 'type' => $type, 'assigned' => $assigned],
            'types' => array_column(SubmissionType::cases(), 'value'),
        ];
    }

    private function viewFor(string $status): string
    {
        foreach (self::VIEWS as $view => $statuses) {
            if (in_array($status, $statuses, true)) {
                return $view;
            }
        }

        return 'new';
    }
}
