<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Privacy\RecordConsentAction;
use App\Data\Privacy\RecordConsentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\UpdateConsentRequest;
use App\Support\CorrelationContext;
use Illuminate\Http\JsonResponse;

final class ConsentController extends Controller
{
    public function store(
        UpdateConsentRequest $request,
        RecordConsentAction $action,
        CorrelationContext $correlation,
    ): JsonResponse {
        /** @var array<string, bool> $decisions */
        $decisions = $request->validated('decisions');
        $sessionKey = $request->session()->getId();
        $appKey = (string) config('app.key');

        $action->execute(new RecordConsentData(
            decisions: $decisions,
            policyVersion: (string) $request->validated('policy_version'),
            subjectKeyHash: hash_hmac('sha256', $sessionKey, $appKey),
            ipHash: hash_hmac('sha256', (string) $request->ip(), $appKey),
            correlationId: $correlation->id(),
        ));

        return response()->json(['recorded' => true]);
    }
}
