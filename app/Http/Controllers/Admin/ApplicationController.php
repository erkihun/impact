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
use App\Support\CorrelationContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = Application::query()
            ->with('vacancy:id,slug,title,locale')
            ->when($request->filled('status'), fn ($query) => $query
                ->where('status', $request->string('status')->toString()))
            ->latest('submitted_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.applications.index', compact('applications'));
    }

    public function show(
        Application $application,
        ChangeApplicationStatusAction $action,
        Request $request,
    ): View {
        $this->authorizeView($request, $application);
        $from = ApplicationStatus::from((string) $application->getRawOriginal('status'));

        return view('admin.applications.show', [
            'application' => $application->load(['vacancy', 'history', 'files.mediaAsset']),
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
