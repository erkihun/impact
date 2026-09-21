<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Engagement\CreateEngagementSubmissionAction;
use App\Data\Engagement\CreateEngagementSubmissionData;
use App\Enums\SubmissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SubmitConsultationRequest;
use App\Support\CorrelationContext;
use App\Support\Settings\EffectiveSettings;
use App\Support\Settings\EngagementSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class EngagementSubmissionController extends Controller
{
    public function store(
        SubmitConsultationRequest $request,
        CreateEngagementSubmissionAction $action,
        CorrelationContext $correlation,
        EngagementSettings $engagement,
        EffectiveSettings $settings,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validated();
        $attachments = $request->file('attachments', []);
        $submission = $action->execute(new CreateEngagementSubmissionData(
            type: SubmissionType::from($validated['type']),
            locale: app()->getLocale() ?: $settings->string('engagement.default_submission_locale'),
            contactName: $validated['contact_name'],
            email: $validated['email'],
            description: $validated['description'],
            correlationId: $correlation->id(),
            policyVersion: $validated['policy_version'],
            organizationName: $validated['organization_name'] ?? null,
            role: $validated['role'] ?? null,
            phone: $validated['phone'] ?? null,
            serviceId: $validated['service_id'] ?? null,
            industryId: $validated['industry_id'] ?? null,
            timeframe: $validated['timeframe'] ?? null,
            budgetRange: $validated['budget_range'] ?? null,
            idempotencyKey: $request->header('Idempotency-Key') ?: hash_hmac(
                'sha256',
                implode('|', [
                    $validated['type'],
                    $validated['email'],
                    hash('sha256', $validated['description']),
                    (string) floor(now('UTC')->timestamp / ($engagement->duplicateWindowHours() * 3600)),
                ]),
                (string) config('app.key'),
            ),
            ipHash: hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            attachments: is_array($attachments) ? array_values($attachments) : [],
        ));

        $payload = [
            'reference' => $submission->reference_no,
            'message' => __('Your request has been received.'),
        ];

        return $request->expectsJson()
            ? response()->json($payload, 201)
            : back()->with('submission', $payload);
    }
}
