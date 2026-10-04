<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\PageComposition\AddPageSectionAction;
use App\Actions\PageComposition\CreatePageCompositionDraftAction;
use App\Actions\PageComposition\DuplicatePageSectionAction;
use App\Actions\PageComposition\RemovePageSectionAction;
use App\Actions\PageComposition\ReorderPageSectionsAction;
use App\Actions\PageComposition\RestorePageSectionAction;
use App\Actions\PageComposition\TransitionPageCompositionAction;
use App\Actions\PageComposition\PublishPageCompositionAction;
use App\Actions\PageComposition\UpdatePageSectionAction;
use App\Data\PageComposition\UpsertSectionData;
use App\Enums\ContentSelectionMode;
use App\Enums\MediaStatus;
use App\Enums\MediaVisibility;
use App\Enums\PageCompositionState;
use App\Enums\PageSectionType;
use App\Enums\VisibilityRule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddPageSectionRequest;
use App\Http\Requests\Admin\ReorderPageSectionsRequest;
use App\Http\Requests\Admin\TransitionPageCompositionRequest;
use App\Http\Requests\Admin\UpdatePageSectionRequest;
use App\Models\MediaAsset;
use App\Models\PageComposition;
use App\Models\PageSection;
use App\Services\PageComposer;
use App\Support\CorrelationContext;
use App\Support\PageSectionRegistry;
use Inertia\Response as View;
use App\Support\Inertia\WorkspacePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

final class PageComposerController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PageComposition::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', \Illuminate\Validation\Rule::enum(PageCompositionState::class)],
        ]);
        $search = strtolower(trim($filters['q'] ?? ''));
        $search = match ($search) {
            'homepage' => 'home',
            'request for proposal' => 'rfp',
            default => str_replace(' ', '.', $search),
        };
        // Filter the newest English version of each page, rather than finding
        // an older published revision when the current draft has a different status.
        $latest = PageComposition::query()->whereNotExists(function ($query): void {
            $query->selectRaw('1')->from('page_compositions as newer')
                ->whereColumn('newer.page_key', 'page_compositions.page_key')
                ->where('newer.locale', 'en')->whereNull('newer.deleted_at')
                ->whereColumn('newer.version_no', '>', 'page_compositions.version_no');
        });
        $summary = [
            'total' => (clone $latest)->count(),
            'published' => (clone $latest)->where('state', PageCompositionState::Published)->count(),
            'in_progress' => (clone $latest)->whereIn('state', [
                PageCompositionState::Draft, PageCompositionState::InReview, PageCompositionState::ChangesRequested,
                PageCompositionState::Approved, PageCompositionState::Scheduled,
            ])->count(),
        ];
        $pages = $latest
            ->when(filled($filters['q'] ?? null), fn ($query) => $query->where(
                'page_key', 'like', '%'.$search.'%',
            ))
            ->when(filled($filters['state'] ?? null), fn ($query) => $query->where('state', $filters['state']))
            ->withCount('sections')->orderBy('page_key')->paginate(30)->withQueryString();
        $pages->through(fn (PageComposition $page): array => [
            'page_key' => $page->page_key,
            'translations' => [$page->only(['id', 'locale', 'version_no', 'state', 'sections_count', 'updated_at', 'lock_version'])],
        ]);

        return WorkspacePage::render('admin.page-compositions.index', [
            'compositions' => $pages,
            'summary' => $summary,
            'filters' => ['q' => $filters['q'] ?? '', 'state' => $filters['state'] ?? ''],
            'states' => PageCompositionState::cases(),
        ]);
    }

    public function edit(
        PageComposition $composition,
        PageSectionRegistry $registry,
    ): View {
        Gate::authorize('view', $composition);
        $composition->load([
            'sections.currentVersion.media.asset',
            'sections.currentVersion.sectionRelations',
            'sections.currentVersion.actions',
        ]);

        return WorkspacePage::render('admin.page-compositions.edit', [
            'composition' => $composition,
            'pageVersions' => PageComposition::query()
                ->where('page_key', $composition->page_key)
                ->orderBy('locale')
                ->latest('version_no')
                ->get(['id', 'locale', 'version_no', 'state']),
            'registry' => $registry->all(),
            'archivedSections' => $composition->sections()->onlyTrashed()->get(),
            'previewUrl' => URL::temporarySignedRoute(
                'admin.page-compositions.preview',
                now('UTC')->addMinutes(30),
                ['composition' => $composition],
            ),
            'eligibleMedia' => MediaAsset::query()
                ->where('visibility', MediaVisibility::Public)
                ->where('scan_status', MediaStatus::Clean)
                ->where('processing_status', MediaStatus::Ready)
                ->orderBy('title')
                ->orderBy('original_name')
                ->limit(200)
                ->get(),
        ]);
    }

    public function preview(PageComposition $composition, PageComposer $composer): View
    {
        Gate::authorize('preview', $composition);

        return \Inertia\Inertia::render('Public/ComposedPage', [
            'composition' => \App\Support\Inertia\CompositionPresenter::present($composer->preview($composition)),
            'meta' => ['title' => __('Secure preview'), 'robots' => 'noindex,nofollow,noarchive'],
        ]);
    }

    public function createDraft(
        PageComposition $composition,
        CreatePageCompositionDraftAction $action,
        CorrelationContext $correlation,
        Request $request,
    ): RedirectResponse {
        $draft = $action->execute($request->user(), $composition, $correlation->id());

        return redirect()
            ->route('admin.page-compositions.edit', $draft)
            ->with('status', __('Editable draft created from the published version.'));
    }

    public function publish(
        Request $request,
        PageComposition $composition,
        PublishPageCompositionAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validate(['lock_version' => ['required', 'integer', 'min:1']]);
        $action->execute($request->user(), $composition, $correlation->id(), (int) $validated['lock_version']);

        return $this->back($composition, __('Page published. Your changes are now live.'));
    }

    public function transition(
        TransitionPageCompositionRequest $request,
        PageComposition $composition,
        TransitionPageCompositionAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validated();
        $updated = $action->execute(
            $request->user(),
            $composition,
            PageCompositionState::from($validated['to']),
            $correlation->id(),
            $validated['comment'] ?? null,
        );

        return redirect()
            ->route('admin.page-compositions.edit', $updated)
            ->with('status', __('Page composition moved to :state.', [
                'state' => (string) str($updated->state->value)->headline(),
            ]));
    }

    public function storeSection(
        AddPageSectionRequest $request,
        PageComposition $composition,
        AddPageSectionAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $action->execute($request->user(), $composition, $this->data($request, $correlation));

        return $this->back($composition, __('Section added.'));
    }

    public function updateSection(
        UpdatePageSectionRequest $request,
        PageComposition $composition,
        PageSection $section,
        UpdatePageSectionAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $action->execute($request->user(), $composition, $section, $this->data($request, $correlation));

        return $this->back($composition, __('Section version saved.'));
    }

    public function reorder(
        ReorderPageSectionsRequest $request,
        PageComposition $composition,
        ReorderPageSectionsAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $data = $request->validated();
        $action->execute(
            $request->user(),
            $composition,
            $data['section_ids'],
            (int) $data['lock_version'],
            $correlation->id(),
        );

        return $this->back($composition, __('Section order saved.'));
    }

    public function duplicate(
        Request $request,
        PageComposition $composition,
        PageSection $section,
        DuplicatePageSectionAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validate(['lock_version' => ['required', 'integer', 'min:1']]);
        $action->execute(
            $request->user(),
            $composition,
            $section,
            (int) $validated['lock_version'],
            $correlation->id(),
        );

        return $this->back($composition, __('Section duplicated.'));
    }

    public function destroySection(
        Request $request,
        PageComposition $composition,
        PageSection $section,
        RemovePageSectionAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validate(['lock_version' => ['required', 'integer', 'min:1']]);
        $action->execute(
            $request->user(),
            $composition,
            $section,
            (int) $validated['lock_version'],
            $correlation->id(),
        );

        return $this->back($composition, __('Section archived.'));
    }

    public function restoreSection(
        Request $request,
        PageComposition $composition,
        string $section,
        RestorePageSectionAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validate(['lock_version' => ['required', 'integer', 'min:1']]);
        $action->execute(
            $request->user(),
            $composition,
            $section,
            (int) $validated['lock_version'],
            $correlation->id(),
        );

        return $this->back($composition, __('Section restored.'));
    }

    private function data(
        AddPageSectionRequest $request,
        CorrelationContext $correlation,
    ): UpsertSectionData {
        $data = $request->validated();

        return new UpsertSectionData(
            type: PageSectionType::from($data['type']),
            variant: $data['variant'],
            editorLabel: $data['editor_label'],
            content: $data['content'],
            presentation: $data['presentation'],
            enabled: (bool) ($data['enabled'] ?? false),
            visibilityRule: VisibilityRule::from($data['visibility_rule']),
            visibleFrom: $data['visible_from'] ?? null,
            visibleUntil: $data['visible_until'] ?? null,
            selectionMode: isset($data['selection_mode'])
                ? ContentSelectionMode::from($data['selection_mode'])
                : null,
            maximumItems: isset($data['maximum_items']) ? (int) $data['maximum_items'] : null,
            expectedLockVersion: (int) $data['lock_version'],
            correlationId: $correlation->id(),
            mediaAssetIds: array_values($data['media_asset_ids'] ?? []),
            replaceMedia: $request->boolean('media_selection_present'),
        );
    }

    private function back(PageComposition $composition, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.page-compositions.edit', $composition)
            ->with('status', $message);
    }
}
