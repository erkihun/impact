<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\ContentItem;
use App\Models\EngagementSubmission;
use App\Models\PageComposition;
use App\Models\PublicationSchedule;
use App\Models\SeoAuditRun;
use App\Models\User;
use App\Support\AuditEventPresenter;
use App\Support\Inertia\WorkspacePage;
use App\Support\Inertia\WorkspaceShell;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Response as View;

/**
 * "Today": what needs this person now, drawn only from areas they can open.
 */
final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $now = CarbonImmutable::now('UTC');

        $attention = [];
        $queue = [];

        if ($user->hasPermission('pages.view') || $user->hasPermission('content.view')) {
            $pagesInReview = $user->hasPermission('pages.view')
                ? PageComposition::query()->where('locale', 'en')->where('state', 'in_review')->latest('updated_at')->limit(5)->get()
                : collect();
            $contentInReview = $user->hasPermission('content.view')
                ? $this->englishContent()->with(['currentVersion', 'owner'])->where('status', 'in_review')->latest('updated_at')->limit(5)->get()
                : collect();
            $oldest = $pagesInReview->pluck('updated_at')->merge($contentInReview->pluck('updated_at'))->filter()->min();
            $attention[] = [
                'key' => 'review', 'icon' => 'case-studies', 'tone' => 'info',
                'count' => $pagesInReview->count() + $contentInReview->count(),
                'label' => 'Waiting for review',
                'hint' => $oldest ? 'Oldest sent '.$oldest->diffForHumans() : 'Nothing is waiting',
                'href' => $pagesInReview->isNotEmpty() || ! $user->hasPermission('content.view') ? '/admin/page-compositions?state=in_review' : '/admin/content?status=in_review',
            ];
            foreach ($pagesInReview as $page) {
                $queue[] = [
                    'icon' => 'layout', 'title' => $this->pageTitle((string) $page->page_key),
                    'meta' => 'Website page · sent for review '.$page->updated_at?->diffForHumans(),
                    'status' => 'in_review', 'statusText' => 'In review',
                    'href' => route('admin.page-compositions.edit', $page, false), 'action' => 'Review', 'primary' => true,
                ];
            }
            foreach ($contentInReview as $item) {
                $queue[] = [
                    'icon' => 'rfp', 'title' => $item->currentVersion->title ?? 'Untitled draft',
                    'meta' => 'Content draft · from '.($item->owner->name ?? 'a colleague').' · '.$item->updated_at?->diffForHumans(),
                    'status' => 'in_review', 'statusText' => 'In review',
                    'href' => route('admin.content.show', $item, false), 'action' => 'Review', 'primary' => true,
                ];
            }

            $scheduled = PublicationSchedule::query()->where('status', 'pending')->whereBetween('publish_at', [$now, $now->addDays(7)])->orderBy('publish_at')->get(['publish_at']);
            $scheduledPages = $user->hasPermission('pages.view')
                ? PageComposition::query()->where('state', 'scheduled')->whereBetween('publish_at', [$now, $now->addDays(7)])->orderBy('publish_at')->get(['publish_at'])
                : collect();
            $next = $scheduled->pluck('publish_at')->merge($scheduledPages->pluck('publish_at'))->filter()->map(fn ($at) => CarbonImmutable::parse($at))->min();
            $attention[] = [
                'key' => 'scheduled', 'icon' => 'events', 'tone' => 'scheduled',
                'count' => $scheduled->count() + $scheduledPages->count(),
                'label' => 'Publishing this week',
                'hint' => $next ? 'Next on '.$next->setTimezone(config('app.timezone'))->format('l \a\t H:i') : 'Nothing scheduled',
                'href' => $scheduledPages->isNotEmpty() ? '/admin/page-compositions?state=scheduled' : '/admin/content?status=scheduled',
            ];
        }

        if ($user->hasPermission('engagement.view')) {
            $newRequests = EngagementSubmission::query()->whereIn('status', WorkspaceShell::NEW_REQUEST_STATUSES);
            $unassigned = (clone $newRequests)->whereNull('assigned_to')->count();
            $attention[] = [
                'key' => 'requests', 'icon' => 'contact', 'tone' => 'attention',
                'count' => (clone $newRequests)->count(),
                'label' => 'New requests to triage',
                'hint' => $unassigned === 0 ? 'All assigned' : $unassigned.' not assigned to anyone',
                'href' => '/admin/engagement',
            ];
            foreach ((clone $newRequests)->with('assignee:id,name')->orderByRaw('assigned_to is not null')->latest('submitted_at')->limit(3)->get() as $submission) {
                $queue[] = [
                    'icon' => 'contact', 'title' => $submission->organization_name ?: $submission->contact_name,
                    'meta' => Str::headline((string) $submission->getRawOriginal('type')).' · received '.$submission->submitted_at->diffForHumans().' · '.($submission->assignee?->name ? 'assigned to '.$submission->assignee->name : 'not assigned'),
                    'status' => 'new', 'statusText' => 'New',
                    'href' => route('admin.engagement.show', $submission, false), 'action' => 'Triage', 'primary' => false,
                ];
            }
        }

        if ($user->hasPermission('applications.view')) {
            $received = Application::query()->with('vacancy:id,title,closes_at')->where('status', ApplicationStatus::Received->value)->get(['id', 'vacancy_id']);
            $byVacancy = $received->groupBy('vacancy_id');
            $attention[] = [
                'key' => 'applications', 'icon' => 'careers', 'tone' => 'success',
                'count' => $received->count(),
                'label' => 'Applications to screen',
                'hint' => match ($byVacancy->count()) {
                    0 => 'Nothing new',
                    1 => 'All for '.($received->first()->vacancy->title ?? 'one vacancy'),
                    default => 'Across '.$byVacancy->count().' vacancies',
                },
                'href' => '/admin/applications',
            ];
            foreach ($byVacancy->take(3) as $vacancyId => $applications) {
                $vacancy = $applications->first()->vacancy;
                $queue[] = [
                    'icon' => 'careers', 'title' => $vacancy->title ?? 'Vacancy',
                    'meta' => trans_choice(':count new application|:count new applications', $applications->count(), ['count' => $applications->count()]).($vacancy?->closes_at ? ' · closes '.$vacancy->closes_at->format('j F') : ''),
                    'status' => 'received', 'statusText' => 'To screen',
                    'href' => '/admin/applications?vacancy='.$vacancyId, 'action' => 'Screen', 'primary' => false,
                ];
            }
        }

        if ($user->hasPermission('content.update')) {
            foreach ($this->englishContent()->with('currentVersion')->where('owner_id', $user->getKey())->whereIn('status', ['draft', 'changes_requested'])->latest('updated_at')->limit(3)->get() as $item) {
                $requested = $item->getRawOriginal('status') === 'changes_requested';
                $queue[] = [
                    'icon' => 'rfp', 'title' => $item->currentVersion->title ?? 'Untitled draft',
                    'meta' => ($requested ? 'Changes requested' : 'Your draft').' · edited '.$item->updated_at?->diffForHumans(),
                    'status' => $requested ? 'changes_requested' : 'draft', 'statusText' => $requested ? 'Changes requested' : 'Draft',
                    'href' => route('admin.content.show', $item, false), 'action' => 'Continue', 'primary' => false,
                ];
            }
        }

        $latestAudit = $user->hasPermission('seo.manage') ? SeoAuditRun::query()->latest('created_at')->first() : null;

        return WorkspacePage::render('admin.dashboard', [
            'firstName' => Str::of((string) $user->name)->before(' ')->toString(),
            'attention' => collect($attention)->sortBy(fn (array $tile): int => array_search($tile['key'], ['review', 'requests', 'applications', 'scheduled'], true))->values()->all(),
            'queue' => array_slice($queue, 0, 8),
            'health' => $user->hasPermission('seo.manage') ? ($latestAudit === null ? ['checked' => false] : [
                'checked' => true,
                'blocking' => (int) $latestAudit->blocking_count,
                'warnings' => (int) $latestAudit->warning_count,
                'information' => (int) $latestAudit->information_count,
                'checkedAt' => $latestAudit->created_at?->diffForHumans(),
            ]) : null,
            'activity' => $user->hasPermission('audit.view')
                ? AuditEvent::query()->with('actor:id,name')->latest('created_at')->limit(5)->get()->map(AuditEventPresenter::present(...))->all()
                : [],
        ]);
    }

    /** @return Builder<ContentItem> */
    private function englishContent()
    {
        return ContentItem::query()->whereHas('currentVersion', fn ($query) => $query->where('locale', 'en'));
    }

    private function pageTitle(string $key): string
    {
        return match (true) {
            $key === 'home' => 'Homepage',
            $key === 'rfp' => 'Request for proposal',
            str_ends_with($key, '.index') => Str::headline(Str::before($key, '.index')).' overview',
            str_ends_with($key, '.show') => Str::headline(Str::before($key, '.show')).' detail page',
            default => Str::headline(str_replace('.', ' ', $key)),
        };
    }
}
