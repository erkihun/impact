<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Recruitment\ChangeApplicationStatusAction;
use App\Data\Recruitment\ChangeApplicationStatusData;
use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeApplicationStatusRequest;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\CorrelationContext;
use App\Support\Inertia\WorkspacePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as View;

final class ApplicationController extends Controller
{
    /** Board columns, in hiring order. */
    private const OPEN = ['received', 'screening', 'shortlisted', 'interview', 'offered', 'hired'];

    private const CLOSED = ['rejected', 'withdrawn', 'anonymized'];

    /** The hiring board: one column per stage, for one vacancy or all of them. */
    public function index(Request $request, ChangeApplicationStatusAction $action): View
    {
        $vacancies = Vacancy::query()->latest()->get(['id', 'title', 'location', 'closes_at', 'status']);
        $vacancyId = $vacancies->contains('id', $request->query('vacancy')) ? (string) $request->query('vacancy') : '';
        $showClosed = $request->boolean('closed');
        $scope = fn () => Application::query()->when($vacancyId !== '', fn ($query) => $query->where('vacancy_id', $vacancyId));

        $applications = $scope()
            ->with('vacancy:id,title')
            ->withCount('files')
            ->whereIn('status', $showClosed ? [...self::OPEN, ...self::CLOSED] : self::OPEN)
            ->latest('submitted_at')
            ->limit(300)
            ->get(['id', 'vacancy_id', 'applicant_name', 'status', 'submitted_at'])
            ->map(fn (Application $application): array => [
                'id' => (string) $application->id,
                'name' => (string) $application->applicant_name,
                'status' => (string) $application->getRawOriginal('status'),
                'submitted_at' => $application->submitted_at->toAtomString(),
                'vacancy' => $application->vacancy?->title,
                'files' => (int) $application->files_count,
                'moves' => array_map(
                    static fn (ApplicationStatus $status): string => $status->value,
                    $action->allowedDestinations(ApplicationStatus::from((string) $application->getRawOriginal('status'))),
                ),
            ]);

        return WorkspacePage::render('admin.applications.index', [
            'columns' => collect($showClosed ? [...self::OPEN, ...self::CLOSED] : self::OPEN)
                ->map(fn (string $status): array => ['status' => $status, 'items' => $applications->where('status', $status)->values()->all()])
                ->all(),
            'closedCount' => $scope()->whereIn('status', self::CLOSED)->count(),
            'vacancies' => $vacancies->map(fn (Vacancy $vacancy): array => [
                'id' => (string) $vacancy->id,
                'label' => trim($vacancy->title.' · '.($vacancy->location ?? '').($vacancy->closes_at ? ' · closes '.$vacancy->closes_at->format('j M') : ''), ' ·'),
            ])->all(),
            'filters' => ['vacancy' => $vacancyId, 'closed' => $showClosed],
        ]);
    }

    public function show(
        Application $application,
        ChangeApplicationStatusAction $action,
        Request $request,
    ): View {
        $this->authorizeView($request, $application);
        $from = ApplicationStatus::from((string) $application->getRawOriginal('status'));

        return WorkspacePage::render('admin.applications.show', [
            'application' => $application->load(['vacancy', 'history.actor:id,name', 'files.mediaAsset']),
            'destinations' => $action->allowedDestinations($from),
        ]);
    }

    public function update(
        ChangeApplicationStatusRequest $request,
        Application $application,
        ChangeApplicationStatusAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action->execute($actor, new ChangeApplicationStatusData(
            applicationId: $application->id,
            to: ApplicationStatus::from($request->string('status')->toString()),
            reason: $request->string('reason')->toString(),
            actorId: $actor->id,
            correlationId: $correlation->id(),
        ));

        return back()->with('status', __('Application status updated.'));
    }

    private function authorizeView(Request $request, Application $application): void
    {
        abort_unless($request->user()?->can('view', $application) === true, 403);
    }
}
