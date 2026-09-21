<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Identity\AcceptUserInvitationAction;
use App\Data\Identity\AcceptInvitationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\UserInvitation;
use App\Support\CorrelationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AcceptInvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = UserInvitation::query()
            ->with('user:id,name,email')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_if($invitation === null || ! $invitation->isUsable(), 404);

        return view('auth.accept-invitation', compact('invitation', 'token'));
    }

    public function store(
        AcceptInvitationRequest $request,
        string $token,
        AcceptUserInvitationAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        $user = $action->execute(new AcceptInvitationData(
            token: $token,
            name: $request->string('name')->toString(),
            password: $request->string('password')->toString(),
            correlationId: $correlation->id(),
        ));

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('session_version', $user->session_version);
        $request->session()->put('session_started_at', now('UTC')->timestamp);
        $request->session()->put('last_activity_at', now('UTC')->timestamp);

        if ($user->requiresMfa()) {
            return redirect()->route('mfa.show');
        }

        return redirect()->route($user->isPrivileged() ? 'admin.dashboard' : 'dashboard');
    }
}
