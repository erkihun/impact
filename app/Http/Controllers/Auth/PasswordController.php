<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Settings\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, PasswordPolicy $passwordPolicy): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', ...$passwordPolicy->rules()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'session_version' => $request->user()->session_version + 1,
        ]);
        $request->session()->put('session_version', $request->user()->session_version);

        return back()->with('status', 'password-updated');
    }
}
