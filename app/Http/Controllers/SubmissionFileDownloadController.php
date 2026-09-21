<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\RecordPrivateDownloadAction;
use App\Data\Media\PrivateDownloadAuditData;
use App\Enums\MediaStatus;
use App\Models\EngagementSubmission;
use App\Models\SubmissionFile;
use App\Models\User;
use App\Support\CorrelationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SubmissionFileDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        SubmissionFile $file,
        RecordPrivateDownloadAction $auditDownload,
        CorrelationContext $correlation,
    ): StreamedResponse {
        $file->loadMissing(['submission', 'mediaAsset']);
        Gate::authorize('view', $file->submission);
        $media = $file->mediaAsset;
        abort_unless(
            $media->getRawOriginal('scan_status') === MediaStatus::Clean->value
            && $media->getRawOriginal('processing_status') === MediaStatus::Ready->value,
            404,
        );
        /** @var User $actor */
        $actor = $request->user();
        $auditDownload->execute(new PrivateDownloadAuditData(
            subjectType: EngagementSubmission::class,
            subjectId: $file->submission_id,
            mediaAssetId: $media->id,
            actorId: $actor->id,
            correlationId: $correlation->id(),
            classification: 'engagement_attachment',
        ));

        return Storage::disk($media->disk)->download(
            $media->path,
            basename($media->original_name),
            ['Content-Type' => $media->mime_type, 'X-Content-Type-Options' => 'nosniff'],
        );
    }
}
