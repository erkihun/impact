<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Media\ApproveMediaAction;
use App\Actions\Media\QuarantineUploadAction;
use App\Data\Media\ApproveMediaData;
use App\Data\Media\QuarantineUploadData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveMediaRequest;
use App\Http\Requests\Admin\UploadMediaRequest;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\CorrelationContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class MediaController extends Controller
{
    public function index(): View
    {
        return view('admin.media.index', [
            'assets' => MediaAsset::query()
                ->with('variants')
                ->whereDoesntHave('applicationFiles')
                ->whereDoesntHave('submissionFiles')
                ->latest()
                ->paginate(30),
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
