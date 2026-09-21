<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\Identity\UserInvitationNotification;
use Database\Seeders\LocaleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, LocaleSeeder::class]);
});

it('invites and activates a staff account through a one-use token', function (): void {
    Notification::fake();
    $administrator = User::factory()->create();
    $administrator->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $editor = Role::query()->where('code', 'editor')->sole();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->post('/admin/users/invitations', [
            'name' => 'Invited Editor',
            'email' => 'invited@example.test',
            'locale' => 'am',
            'roles' => [$editor->id],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'invited@example.test')->sole();
    $invitation = UserInvitation::query()->whereBelongsTo($user)->sole();
    expect($user->status)->toBe(UserStatus::Invited)
        ->and($user->email_verified_at)->toBeNull()
        ->and($invitation->token_hash)->toHaveLength(64)
        ->and($invitation->role_ids)->toBe([$editor->id]);
    $this->assertDatabaseHas('audit_events', ['action' => 'identity.user_invited']);

    $token = null;
    Notification::assertSentTo(
        $user,
        UserInvitationNotification::class,
        function (UserInvitationNotification $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        },
    );
    expect($token)->toBeString()->not->toBe('');

    auth()->logout();
    $this->get("/invitations/{$token}")->assertOk()->assertSee('invited@example.test');
    $this->post("/invitations/{$token}", [
        'name' => 'Activated Editor',
        'password' => 'A-strong-invitation-password-2026!',
        'password_confirmation' => 'A-strong-invitation-password-2026!',
    ])->assertRedirect('/mfa');

    expect($user->refresh()->status)->toBe(UserStatus::Active)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('A-strong-invitation-password-2026!', $user->password))->toBeTrue()
        ->and($invitation->refresh()->accepted_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('audit_events', ['action' => 'identity.invitation_accepted']);
});

it('prevents delegated administrators from assigning roles above their authority', function (): void {
    $administrator = User::factory()->create();
    $administrator->roles()->attach(Role::query()->where('code', 'administrator')->sole());
    $superRole = Role::query()->where('code', 'super_administrator')->sole();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->from('/admin/users')
        ->post('/admin/users/invitations', [
            'name' => 'Escalated User',
            'email' => 'escalated@example.test',
            'locale' => 'en',
            'roles' => [$superRole->id],
        ])
        ->assertRedirect('/admin/users')
        ->assertSessionHasErrors('roles');

    $this->assertDatabaseMissing('users', ['email' => 'escalated@example.test']);
});

it('rejects expired and already-used invitation tokens', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Invited]);
    $inviter = User::factory()->create();
    $token = 'known-expired-token';
    UserInvitation::factory()->create([
        'user_id' => $user->id,
        'invited_by' => $inviter->id,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now('UTC')->subMinute(),
    ]);

    $this->get("/invitations/{$token}")->assertNotFound();
    $this->post("/invitations/{$token}", [
        'name' => 'Expired User',
        'password' => 'A-strong-invitation-password-2026!',
        'password_confirmation' => 'A-strong-invitation-password-2026!',
    ])->assertSessionHasErrors('invitation');
});
