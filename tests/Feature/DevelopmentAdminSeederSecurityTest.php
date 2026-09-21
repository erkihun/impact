<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevelopmentAdminSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

it('does not seed a privileged account without an explicit strong development password', function (): void {
    config()->set('impact.development_admin.email', 'admin@impact.test');
    config()->set('impact.development_admin.password');

    $this->seed([PermissionSeeder::class, RoleSeeder::class, DevelopmentAdminSeeder::class]);

    $this->assertDatabaseCount('users', 0);
});

it('creates the development administrator only from an explicit strong secret', function (): void {
    config()->set('impact.development_admin.email', 'impact@local.com');
    config()->set('impact.development_admin.password', 'Impact2018');

    $legacyUser = User::factory()->create(['email' => 'admin@impact.test']);
    $this->seed([PermissionSeeder::class, RoleSeeder::class, DevelopmentAdminSeeder::class]);

    $user = User::query()->sole();
    expect($user->is($legacyUser))->toBeTrue()
        ->and($user->email)->toBe('impact@local.com')
        ->and(Hash::check('Impact2018', $user->password))->toBeTrue()
        ->and($user->mfa_exempt)->toBeTrue()
        ->and($user->roles()->where('code', 'super_administrator')->exists())->toBeTrue();
});

it('runs the complete seed chain without inventing development credentials', function (): void {
    config()->set('impact.development_admin.email');
    config()->set('impact.development_admin.password');

    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('permissions', count(PermissionCatalog::ALL));
    $this->assertDatabaseCount('roles', 10);
});
