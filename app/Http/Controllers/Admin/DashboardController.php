<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\EngagementSubmission;
use App\Models\PublicationSchedule;
use App\Models\User;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, EffectiveSettings $settings): View
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $metrics = [];
        // Work queues carry the records themselves so the dashboard can show what needs
        // attention rather than only how many items exist. Both remain permission-gated.
        $awaitingReview = [];
        $recentWork = [];
        $openEngagements = [];

        if ($user->hasPermission('content.view')) {
            $metrics['draft_content'] = ContentItem::query()->where('status', 'draft')->count();
            $metrics['in_review'] = ContentItem::query()->where('status', 'in_review')->count();
            $metrics['scheduled_publications'] = PublicationSchedule::query()
                ->where('status', 'pending')->count();
            $metrics['stale_content'] = ContentItem::query()
                ->where('updated_at', '<=', now('UTC')->subDays(
                    $settings->integer('content.stale_content_threshold_days'),
                ))
                ->count();

            $awaitingReview = ContentItem::query()
                ->with(['currentVersion', 'owner'])
                ->where('status', 'in_review')
                ->latest('updated_at')
                ->limit(5)
                ->get();

            $recentWork = ContentItem::query()
                ->with(['currentVersion', 'owner'])
                ->latest('updated_at')
                ->limit(5)
                ->get();
        }

        if ($user->hasPermission('engagement.view')) {
            $metrics['open_submissions'] = EngagementSubmission::query()
                ->whereNotIn('status', ['closed', 'rejected'])->count();

            $openEngagements = EngagementSubmission::query()
                ->with('assignee')
                ->whereNotIn('status', ['closed', 'rejected'])
                ->latest('submitted_at')
                ->limit(5)
                ->get();
        }

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'awaitingReview' => $awaitingReview,
            'recentWork' => $recentWork,
            'openEngagements' => $openEngagements,
            'quickActions' => array_values(array_filter([
                $user->hasPermission('content.create')
                    ? ['label' => __('Create content'), 'route' => route('admin.content.create')]
                    : null,
                $user->hasPermission('media.create')
                    ? ['label' => __('Upload media'), 'route' => route('admin.media.index')]
                    : null,
                $user->hasPermission('engagement.view')
                    ? ['label' => __('Review engagement'), 'route' => route('admin.engagement.index')]
                    : null,
                $user->hasPermission('audit.view')
                    ? ['label' => __('View audit log'), 'route' => route('admin.audit.index')]
                    : null,
            ])),
        ]);
    }
}
