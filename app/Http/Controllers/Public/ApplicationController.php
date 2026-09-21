<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Media\QuarantineUploadAction;
use App\Actions\Recruitment\SubmitApplicationAction;
use App\Data\Media\QuarantineUploadData;
use App\Data\Recruitment\SubmitApplicationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SubmitApplicationRequest;
use App\Models\Vacancy;
use App\Support\CorrelationContext;
use Illuminate\Http\RedirectResponse;

final class ApplicationController extends Controller
{
    public function store(
        SubmitApplicationRequest $request,
        string $locale,
        string $slug,
        QuarantineUploadAction $uploads,
        SubmitApplicationAction $applications,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $vacancy = Vacancy::query()
            ->where(compact('locale', 'slug'))
            ->where('status', 'published')
            ->firstOrFail();
        $validated = $request->validated();
        $retentionUntil = now('UTC')
            ->addDays((int) config('impact.retention.application_days', 730))
            ->toDateString();
        $media = $uploads->execute(new QuarantineUploadData(
            file: $request->file('cv'),
            visibility: 'restricted',
            allowedMimeTypes: [
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            locale: $locale,
            retentionUntil: $retentionUntil,
            fieldName: 'cv',
        ));

        $application = $applications->execute(new SubmitApplicationData(
            vacancyId: (string) $vacancy->id,
            applicantName: $validated['applicant_name'],
            email: $validated['email'],
            mediaAssetId: (string) $media->id,
            policyVersion: (string) config('impact.privacy.policy_version'),
            correlationId: $correlation->id(),
            ipHash: hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            phone: $validated['phone'] ?? null,
            coverLetter: $validated['cover_letter'] ?? null,
        ));

        return back()->with('submission', [
            'reference' => $application->reference_no,
            'message' => __('Your application has been received.'),
        ]);
    }
}
