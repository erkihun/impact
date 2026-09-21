<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DevelopmentAdminSeeder extends Seeder
{
    private const LEGACY_EMAIL = 'admin@impact.test';

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $email = config('impact.development_admin.email');
        $password = config('impact.development_admin.password');
        if (! is_string($email)
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ! is_string($password)
            || mb_strlen($password) < 10) {
            $this->command->warn(
                'Development administrator not created: set a valid DEVELOPMENT_ADMIN_EMAIL and a password of at least 10 characters.',
            );

            return;
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null && $email === 'impact@local.com') {
            $user = User::query()->where('email', self::LEGACY_EMAIL)->first();
        }

        $user ??= new User;
        $user->forceFill([
            'email' => $email,
            'name' => 'Development Super Administrator',
            'password' => Hash::make($password),
            'status' => UserStatus::Active,
            'locale' => 'en',
            'email_verified_at' => now('UTC'),
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
            'mfa_confirmed_at' => null,
            'mfa_exempt' => true,
        ])->save();

        $role = Role::query()->where('code', RoleCode::SuperAdministrator->value)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->getKey()]);
    }
}
