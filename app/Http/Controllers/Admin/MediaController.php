<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Media\ApproveMediaAction;
use App\Actions\Media\QuarantineUploadAction;
use App\Contracts\AuditRecorder;
use App\Data\Audit\AuditData;
use App\Data\Media\ApproveMediaData;
use App\Data\Media\QuarantineUploadData;
use App\Enums\MediaStatus;
use App\Enums\MediaVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveMediaRequest;
use App\Http\Requests\Admin\UploadMediaRequest;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\CorrelationContext;
use App\Support\Inertia\WorkspacePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Response as View;

final class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $show = in_array($request->query('show'), ['images', 'documents', 'approval'], true) ? (string) $request->query('show') : '';
        $search = trim((string) $request->query('q', ''));
        $library = fn () => MediaAsset::query()
            ->whereDoesntHave('applicationFiles')
            ->whereDoesntHave('submissionFiles')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('original_name', 'like', '%'.$search.'%')));
        // Approval promotes a clean, processed file out of quarantine into media/.
        $awaitingApproval = fn ($query) => $query->where('scan_status', MediaStatus::Clean->value)
            ->where('processing_status', MediaStatus::Ready->value)
            ->where('path', 'not like', 'media/%');
        $filter = fn ($query, string $show) => match ($show) {
            'images' => $query->where('mime_type', 'like', 'image/%'),
            'documents' => $query->where('mime_type', 'not like', 'image/%'),
            'approval' => $awaitingApproval($query),
            default => $query,
        };

        $assets = $filter($library(), $show)
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(fn (MediaAsset $asset): array => [
                'id' => (string) $asset->id,
                'title' => $asset->title,
                'original_name' => $asset->original_name,
                'alt_text' => $asset->alt_text,
                'mime_type' => $asset->mime_type,
                'size_bytes' => (int) $asset->size_bytes,
                'visibility' => (string) $asset->getRawOriginal('visibility'),
                'scan_status' => (string) $asset->getRawOriginal('scan_status'),
                'processing_status' => (string) $asset->getRawOriginal('processing_status'),
                'created_at' => $asset->created_at?->toAtomString(),
                'is_image' => str_starts_with((string) $asset->mime_type, 'image/'),
                'needs_approval' => $asset->getRawOriginal('scan_status') === MediaStatus::Clean->value
                    && $asset->getRawOriginal('processing_status') === MediaStatus::Ready->value
                    && ! str_starts_with((string) $asset->path, 'media/'),
                'url' => $asset->getRawOriginal('visibility') === MediaVisibility::Public->value
                    && $asset->getRawOriginal('scan_status') === MediaStatus::Clean->value
                    && $asset->getRawOriginal('processing_status') === MediaStatus::Ready->value
                    && str_starts_with((string) $asset->path, 'media/')
                    ? Storage::disk($asset->disk)->url($asset->path) : null,
            ]);

        return WorkspacePage::render('admin.media.index', [
            'assets' => $assets,
            'counts' => [
                'all' => $library()->count(),
                'images' => $filter($library(), 'images')->count(),
                'documents' => $filter($library(), 'documents')->count(),
                'approval' => $filter($library(), 'approval')->count(),
            ],
            'filters' => ['show' => $show, 'q' => $search],
        ]);
    }

    public function store(
        UploadMediaRequest $request,
        QuarantineUploadAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $validated = $request->validated();
        $action->execute(new QuarantineUploadData(
            file: $request->file('file'),
            visibility: $validated['visibility'],
            allowedMimeTypes: [
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/pdf',
            ],
            uploadedBy: (string) $request->user()->getKey(),
            locale: $validated['locale'] ?? null,
            correlationId: $correlation->id(),
            title: $validated['title'] ?? null,
            altText: $validated['alt_text'] ?? null,
        ));

        return back()->with('status', __('File quarantined for security scanning.'));
    }

    /** Title and screen-reader description; the file itself never changes here. */
    public function update(Request $request, MediaAsset $media, AuditRecorder $audit, CorrelationContext $correlation): RedirectResponse
    {
        abort_if($media->applicationFiles()->exists() || $media->submissionFiles()->exists(), 403);
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:220'],
            'alt_text' => ['nullable', 'string', 'max:500'],
        ]);
        $before = hash('sha256', $media->toJson());
        $media->update(['title' => $validated['title'] ?? null, 'alt_text' => $validated['alt_text'] ?? null]);
        $audit->record(new AuditData(
            action: 'media.details_updated',
            auditableType: MediaAsset::class,
            auditableId: (string) $media->id,
            actorId: (string) $request->user()?->getKey(),
            correlationId: $correlation->id(),
            beforeHash: $before,
            afterHash: hash('sha256', $media->fresh()->toJson()),
        ));

        return back()->with('status', __('File details saved.'));
    }

    public function approve(
        ApproveMediaRequest $request,
        MediaAsset $media,
        ApproveMediaAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action->execute($actor, new ApproveMediaData(
            mediaAssetId: $media->id,
            actorId: $actor->id,
            correlationId: $correlation->id(),
        ));

        return back()->with('status', __('Media approved and promoted from quarantine.'));
    }
}
