<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

it('prevents staff accounts from self-deleting and preserves administrative identity', function (): void {
    $administrator = User::factory()->create();
    $administrator->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->delete('/profile', ['password' => 'password'])
        ->assertSessionHasErrors('password', null, 'userDeletion');

    $this->assertDatabaseHas('users', ['id' => $administrator->id]);
});

it('allows a legacy role-free account to self-delete with audit evidence', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(privilegedSession($user))
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseHas('audit_events', [
        'action' => 'identity.account_self_deleted',
        'actor_id' => null,
    ]);
});
