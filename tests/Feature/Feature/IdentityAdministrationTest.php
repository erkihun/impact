<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\LocaleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, LocaleSeeder::class]);
});

it('updates a user, audits the change, and revokes existing sessions', function (): void {
    $administrator = User::factory()->create();
    $administrator->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $target = User::factory()->create();
    $editor = Role::query()->where('code', 'editor')->sole();
    $reviewer = Role::query()->where('code', 'reviewer')->sole();
    $target->roles()->attach($editor);

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->patch("/admin/users/{$target->id}", [
            'name' => 'Updated Person',
            'email' => 'updated@example.test',
            'locale' => 'am',
            'status' => 'suspended',
            'roles' => [$reviewer->id],
        ])
        ->assertRedirect();

    expect($target->refresh()->status)->toBe(UserStatus::Suspended)
        ->and($target->roles()->sole()->code)->toBe('reviewer')
        ->and($target->session_version)->toBe(2);
    $this->assertDatabaseHas('audit_events', ['action' => 'identity.user_updated']);
});

it('prevents an administrator from removing their own administrative access', function (): void {
    $administrator = User::factory()->create();
    $administrator->roles()->attach(Role::query()->where('code', 'super_administrator')->sole());
    $editor = Role::query()->where('code', 'editor')->sole();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->from("/admin/users/{$administrator->id}/edit")
        ->patch("/admin/users/{$administrator->id}", [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'locale' => 'en',
            'status' => 'active',
            'roles' => [$editor->id],
        ])
        ->assertSessionHasErrors('status');
});

it('updates role permissions and invalidates assigned user sessions', function (): void {
    $administrator = User::factory()->create();
    $superRole = Role::query()->where('code', 'super_administrator')->sole();
    $administrator->roles()->attach($superRole);
    $editor = Role::query()->where('code', 'editor')->sole();
    $editorUser = User::factory()->create();
    $editorUser->roles()->attach($editor);
    $permissions = Permission::query()->whereIn('code', ['content.view', 'content.update'])->get();

    $this->actingAs($administrator)
        ->withSession(privilegedSession($administrator))
        ->patch("/admin/roles/{$editor->id}", [
            'name' => 'Editorial Author',
            'permissions' => $permissions->pluck('id')->all(),
        ])
        ->assertRedirect();

    expect($editor->refresh()->name)->toBe('Editorial Author')
        ->and($editor->permissions()->count())->toBe(2)
        ->and($editorUser->refresh()->session_version)->toBe(2);
    $this->assertDatabaseHas('audit_events', ['action' => 'identity.role_updated']);
});

it('denies user administration without the server-side permission', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/admin/users')
        ->assertForbidden();
});
