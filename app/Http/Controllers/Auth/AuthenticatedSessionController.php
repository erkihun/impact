<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $now = now('UTC')->timestamp;
        $request->session()->put([
            'authenticated_at' => $now,
            'last_session_activity_at' => $now,
            'session_version' => $request->user()->session_version,
        ]);
        $request->user()->forceFill([
            'last_login_at' => now('UTC'),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        if ($request->user()->requiresMfa()) {
            return redirect()->route('mfa.show');
        }

        return $request->user()->isPrivileged()
            ? redirect()->intended(route('admin.dashboard', absolute: false))
            : redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
