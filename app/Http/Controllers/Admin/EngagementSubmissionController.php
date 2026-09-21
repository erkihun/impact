<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Engagement\UpdateSubmissionAction;
use App\Data\Engagement\UpdateSubmissionData;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSubmissionRequest;
use App\Models\EngagementSubmission;
use App\Models\User;
use App\Support\CorrelationContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class EngagementSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EngagementSubmission::class);
        $submissions = EngagementSubmission::query()
            ->with('assignee')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->latest('submitted_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.engagement.index', compact('submissions'));
    }

    public function show(
        EngagementSubmission $submission,
        UpdateSubmissionAction $action,
    ): View {
        Gate::authorize('view', $submission);
        $submission->load(['history', 'assignee', 'service', 'files.mediaAsset']);

        return view('admin.engagement.show', [
            'submission' => $submission,
            'assignees' => User::query()->where('status', 'active')->orderBy('name')->get(),
            'statuses' => $action->allowedDestinations(SubmissionStatus::from(
                (string) $submission->getRawOriginal('status'),
            )),
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

        return back()->with('status', __('Submission updated.'));
    }
}
