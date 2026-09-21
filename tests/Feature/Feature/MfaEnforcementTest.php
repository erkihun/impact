<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

it('redirects privileged users to MFA before administration', function (): void {
    $user = User::factory()->create([
        'mfa_secret' => app(Google2FA::class)->generateSecretKey(32),
        'mfa_confirmed_at' => now('UTC'),
    ]);
    $user->roles()->attach(Role::query()->where('code', 'publisher')->sole());

    $this->actingAs($user)
        ->withSession([
            'authenticated_at' => now('UTC')->timestamp,
            'last_session_activity_at' => now('UTC')->timestamp,
            'session_version' => $user->session_version,
        ])
        ->get('/admin')
        ->assertRedirect(route('mfa.show'));
});

it('allows the MFA-exempt development administrator into all administration areas', function (): void {
    $user = User::factory()->create(['mfa_exempt' => true]);
    $user->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $session = privilegedSession($user);
    unset($session['mfa_verified_at']);

    $this->actingAs($user)
        ->withSession($session)
        ->get('/admin')
        ->assertOk();

    $this->actingAs($user)
        ->withSession($session)
        ->get('/admin/settings/diagnostics')
        ->assertOk();
});

it('sends the MFA-exempt development administrator directly to administration after login', function (): void {
    $user = User::factory()->create([
        'email' => 'impact@local.com',
        'password' => 'Impact2018',
        'mfa_exempt' => true,
    ]);
    $user->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());

    $this->post('/login', [
        'email' => 'impact@local.com',
        'password' => 'Impact2018',
    ])->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

it('enrolls MFA and stores only hashed recovery codes', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'publisher')->sole());
    $session = privilegedSession($user);
    unset($session['mfa_verified_at']);

    $this->actingAs($user)->withSession($session)->get('/mfa')->assertOk();
    $secret = $user->refresh()->mfa_secret;
    expect($secret)->toBeString();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $response = $this->actingAs($user)->withSession($session)->post('/mfa/enroll', ['code' => $code]);

    $response->assertOk()->assertViewIs('auth.mfa-recovery');
    $recoveryCodes = $response->viewData('codes');
    expect($user->refresh()->mfa_confirmed_at)->not->toBeNull()
        ->and($user->mfa_recovery_codes)->toHaveCount(8)
        ->and($user->mfa_recovery_codes)->not->toContain($recoveryCodes[0]);
    $this->assertDatabaseHas('audit_events', ['action' => 'identity.mfa_enabled']);
});

it('accepts a valid TOTP challenge and rejects a revoked session', function (): void {
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey(32);
    $user = User::factory()->create(['mfa_secret' => $secret, 'mfa_confirmed_at' => now('UTC')]);
    $user->roles()->attach(Role::query()->where('code', 'publisher')->sole());
    $session = privilegedSession($user);
    unset($session['mfa_verified_at']);

    $this->actingAs($user)
        ->withSession($session)
        ->post('/mfa/challenge', ['code' => $google2fa->getCurrentOtp($secret)])
        ->assertRedirect(route('admin.dashboard'));

    $user->increment('session_version');
    $this->actingAs($user)
        ->withSession(array_merge(privilegedSession($user), ['session_version' => 1]))
        ->get('/dashboard')
        ->assertRedirect(route('login'));
});
