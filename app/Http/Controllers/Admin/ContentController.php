<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Content\CreateContentAction;
use App\Actions\Content\RollbackContentAction;
use App\Actions\Content\UpdateContentAction;
use App\Actions\Workflow\TransitionContentWorkflowAction;
use App\Data\Content\CreateContentData;
use App\Data\Content\RollbackContentData;
use App\Data\Content\UpdateContentData;
use App\Data\Workflow\TransitionContentData;
use App\Enums\ContentType;
use App\Enums\ContentWorkflowState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RollbackContentRequest;
use App\Http\Requests\Admin\StoreContentRequest;
use App\Http\Requests\Admin\TransitionContentRequest;
use App\Http\Requests\Admin\UpdateContentRequest;
use App\Models\ContentItem;
use App\Models\ContentVersion;
use App\Services\Workflow\ContentWorkflow;
use App\Support\CorrelationContext;
use App\Support\Settings\EffectiveSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

final class ContentController extends Controller
{
    public function index(Request $request, EffectiveSettings $settings): View
    {
        Gate::authorize('viewAny', ContentItem::class);
        $items = ContentItem::query()
            ->with(['currentVersion', 'owner'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->latest()
            ->paginate(min(
                $settings->integer('content.default_items_per_page'),
                $settings->integer('performance.maximum_pagination_size'),
            ))
            ->withQueryString();

        return view('admin.content.index', compact('items'));
    }

    public function create(EffectiveSettings $settings): View
    {
        abort_unless(request()->user()?->hasPermission('content.create'), 403);

        return view('admin.content.create', [
            'types' => ContentType::cases(),
            'locales' => $settings->array('localization.enabled_locales'),
        ]);
    }

    public function store(
        StoreContentRequest $request,
        CreateContentAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validated();
        $content = $action->execute(new CreateContentData(
            type: ContentType::from($validated['type']),
            ownerId: (string) $request->user()->getKey(),
            locale: $validated['locale'],
            slug: $validated['slug'],
            title: $validated['title'],
            body: ['content' => $validated['body']],
            correlationId: $correlation->id(),
            summary: $validated['summary'] ?? null,
        ));

        return redirect()->route('admin.content.show', $content)
            ->with('status', __('Draft content created.'));
    }

    public function show(
        ContentItem $content,
        ContentWorkflow $workflow,
        EffectiveSettings $settings,
    ): View {
        Gate::authorize('view', $content);
        $content->load(['currentVersion', 'versions', 'workflowEvents.actor', 'owner']);

        $state = ContentWorkflowState::from((string) $content->getRawOriginal('status'));

        return view('admin.content.show', [
            'content' => $content,
            'allowedTransitions' => $workflow->allowedDestinations($state),
            'previewUrls' => $content->versions->mapWithKeys(
                fn (ContentVersion $version): array => [
                    $version->id => URL::temporarySignedRoute(
                        'admin.content.preview',
                        now('UTC')->addMinutes($settings->integer('content.preview_expiry_minutes')),
                        ['content' => $content, 'version' => $version],
                    ),
                ],
            ),
            'rollbackVersionIds' => $content->workflowEvents
                ->filter(fn ($event): bool => in_array(
                    (string) $event->getRawOriginal('to_state'),
                    [
                        ContentWorkflowState::Approved->value,
                        ContentWorkflowState::Published->value,
                    ],
                    true,
                ))
                ->pluck('content_version_id')
                ->unique()
                ->all(),
        ]);
    }

    public function edit(ContentItem $content): View
    {
        Gate::authorize('update', $content);
        $content->load('currentVersion');

        return view('admin.content.edit', compact('content'));
    }

    public function update(
        UpdateContentRequest $request,
        ContentItem $content,
        UpdateContentAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validated();
        $action->execute($request->user(), new UpdateContentData(
            contentItemId: (string) $content->getKey(),
            expectedCurrentVersionId: $validated['expected_current_version_id'],
            actorId: (string) $request->user()->getKey(),
            title: $validated['title'],
            summary: $validated['summary'] ?? null,
            body: $validated['body'],
            correlationId: $correlation->id(),
        ));

        return redirect()->route('admin.content.show', $content)
            ->with('status', __('A new draft revision was created.'));
    }

    public function preview(
        ContentItem $content,
        ContentVersion $version,
    ): Response {
        Gate::authorize('view', $content);
        abort_unless((string) $version->content_item_id === (string) $content->getKey(), 404);

        return response()
            ->view('admin.content.preview', compact('content', 'version'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function rollback(
        RollbackContentRequest $request,
        ContentItem $content,
        RollbackContentAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validated();
        $action->execute($request->user(), new RollbackContentData(
            contentItemId: (string) $content->getKey(),
            expectedCurrentVersionId: $validated['expected_current_version_id'],
            sourceVersionId: $validated['source_version_id'],
            actorId: (string) $request->user()->getKey(),
            reason: $validated['reason'],
            correlationId: $correlation->id(),
        ));

        return redirect()->route('admin.content.show', $content)
            ->with('status', __('The historical version was restored as a new draft revision.'));
    }

    public function transition(
        TransitionContentRequest $request,
        ContentItem $content,
        TransitionContentWorkflowAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validated();
        $action->execute($request->user(), $content, new TransitionContentData(
            contentVersionId: $validated['content_version_id'],
            to: ContentWorkflowState::from($validated['to']),
            correlationId: $correlation->id(),
            note: $validated['note'] ?? null,
            publishAt: $validated['publish_at'] ?? null,
            unpublishAt: $validated['unpublish_at'] ?? null,
        ));

        return back()->with('status', __('Workflow state updated.'));
    }
}
