<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Identity\ConfirmMfaEnrollmentAction;
use App\Actions\Identity\VerifyMfaChallengeAction;
use App\Data\Identity\MfaChallengeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmMfaEnrollmentRequest;
use App\Http\Requests\Auth\MfaChallengeRequest;
use App\Models\User;
use App\Services\Identity\TotpService;
use App\Support\CorrelationContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MfaController extends Controller
{
    public function show(Request $request, TotpService $totp): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->requiresMfa()) {
            return redirect()->intended(
                route($user->isPrivileged() ? 'admin.dashboard' : 'dashboard'),
            );
        }
        if ($request->session()->has('mfa_verified_at')) {
            return redirect()->intended(route('admin.dashboard'));
        }
        if ($user->mfa_confirmed_at === null && $user->mfa_secret === null) {
            $user->forceFill(['mfa_secret' => $totp->generateSecret()])->save();
        }

        return view('auth.mfa', [
            'enrolling' => $user->mfa_confirmed_at === null,
            'secret' => $user->mfa_confirmed_at === null ? $user->mfa_secret : null,
            'provisioningUri' => $user->mfa_confirmed_at === null && is_string($user->mfa_secret)
                ? $totp->provisioningUri($user->email, $user->mfa_secret)
                : null,
        ]);
    }

    public function confirm(
        ConfirmMfaEnrollmentRequest $request,
        ConfirmMfaEnrollmentAction $action,
        CorrelationContext $correlation,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $codes = $action->execute($user, $request->string('code')->toString(), $correlation->id());
        $request->session()->put([
            'mfa_verified_at' => now('UTC')->timestamp,
            'session_version' => $user->refresh()->session_version,
        ]);

        return view('auth.mfa-recovery', ['codes' => $codes]);
    }

    public function verify(
        MfaChallengeRequest $request,
        VerifyMfaChallengeAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $action->execute(new MfaChallengeData(
            userId: $user->id,
            code: $request->string('code')->toString(),
            correlationId: $correlation->id(),
        ));
        $request->session()->put('mfa_verified_at', now('UTC')->timestamp);

        return redirect()->intended(route('admin.dashboard'));
    }
}
