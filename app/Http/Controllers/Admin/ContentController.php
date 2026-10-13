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
use App\Models\User;
use App\Services\Workflow\ContentWorkflow;
use App\Support\CorrelationContext;
use App\Support\Settings\EffectiveSettings;
use Inertia\Response as View;
use App\Support\Inertia\WorkspacePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

final class ContentController extends Controller
{
    public function index(Request $request, EffectiveSettings $settings): View
    {
        Gate::authorize('viewAny', ContentItem::class);
        $items = ContentItem::query()
            ->whereHas('currentVersion', fn ($query) => $query->where('locale', 'en'))
            ->with(['currentVersion', 'owner'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->latest()
            ->paginate(min(
                $settings->integer('content.default_items_per_page'),
                $settings->integer('performance.maximum_pagination_size'),
            ))
            ->withQueryString();

        return WorkspacePage::render('admin.content.index', ['items' => $items, 'types' => ContentType::cases(), 'states' => ContentWorkflowState::cases()]);
    }

    public function create(EffectiveSettings $settings): View
    {
        abort_unless(request()->user()?->hasPermission('content.create'), 403);

        return WorkspacePage::render('admin.content.create', [
            'types' => ContentType::cases(),
            'locales' => $settings->array('localization.enabled_locales'),
            'canPublish' => Gate::allows('publish', ContentItem::class),
        ]);
    }

    public function store(
        StoreContentRequest $request,
        CreateContentAction $action,
        TransitionContentWorkflowAction $transition,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validated();
        $publish = (bool) ($validated['publish'] ?? false);

        // Creating and publishing happen together, so a blocked publication
        // leaves no orphaned draft behind.
        $content = DB::transaction(function () use ($request, $action, $transition, $correlation, $validated, $publish): ContentItem {
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

            return $publish
                ? $this->publishNow($request->user(), $content, $transition, $correlation, 'published_on_create')
                : $content;
        });

        return redirect()->route('admin.content.show', $content)
            ->with('status', $publish ? __('Content published.') : __('Draft content created.'));
    }

    public function show(
        ContentItem $content,
        ContentWorkflow $workflow,
        EffectiveSettings $settings,
    ): View {
        Gate::authorize('view', $content);
        $content->load(['currentVersion', 'versions' => fn ($query) => $query->where('locale', 'en'), 'workflowEvents.actor', 'owner']);

        $state = ContentWorkflowState::from((string) $content->getRawOriginal('status'));

        return WorkspacePage::render('admin.content.show', [
            'content' => $content,
            'allowedTransitions' => array_values(array_filter($workflow->allowedDestinations($state), fn (ContentWorkflowState $to): bool => Gate::allows('transition', [$content, $to]))),
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

        return WorkspacePage::render('admin.content.edit', [
            'content' => $content,
            'canPublish' => Gate::allows('publish', ContentItem::class),
        ]);
    }

    public function update(
        UpdateContentRequest $request,
        ContentItem $content,
        UpdateContentAction $action,
        TransitionContentWorkflowAction $transition,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validated();
        $publish = (bool) ($validated['publish'] ?? false);

        DB::transaction(function () use ($request, $content, $action, $transition, $correlation, $validated, $publish): void {
            $revised = $action->execute($request->user(), new UpdateContentData(
                contentItemId: (string) $content->getKey(),
                expectedCurrentVersionId: $validated['expected_current_version_id'],
                actorId: (string) $request->user()->getKey(),
                title: $validated['title'],
                summary: $validated['summary'] ?? null,
                body: $validated['body'],
                correlationId: $correlation->id(),
            ));

            if ($publish) {
                $this->publishNow($request->user(), $revised, $transition, $correlation, 'published_on_save');
            }
        });

        return redirect()->route('admin.content.show', $content)
            ->with('status', $publish ? __('The new revision was published.') : __('A new draft revision was created.'));
    }

    public function preview(
        ContentItem $content,
        ContentVersion $version,
    ): View {
        Gate::authorize('view', $content);
        abort_unless($version->locale === 'en', 404);
        abort_unless((string) $version->content_item_id === (string) $content->getKey(), 404);

        return WorkspacePage::render('admin.content.preview', [
            'content' => $content, 'version' => $version,
            'meta' => ['title' => $version->title, 'robots' => 'noindex,nofollow,noarchive'],
        ]);
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

    /**
     * Publishes the current version straight away. Publication checks that
     * fail are reported against the "publish" field of the form that asked.
     */
    private function publishNow(
        User $actor,
        ContentItem $content,
        TransitionContentWorkflowAction $transition,
        CorrelationContext $correlation,
        string $note,
    ): ContentItem {
        try {
            return $transition->execute($actor, $content, new TransitionContentData(
                contentVersionId: (string) $content->current_version_id,
                to: ContentWorkflowState::Published,
                correlationId: $correlation->id(),
                note: $note,
            ));
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'publish' => Arr::flatten($exception->errors()),
            ]);
        }
    }
}
