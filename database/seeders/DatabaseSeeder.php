<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            LocaleSeeder::class,
            SettingSeeder::class,
            DevelopmentAdminSeeder::class,
            PublicContentSeeder::class,
            PageCompositionSeeder::class,
            NavigationConfigurationSeeder::class,
        ]);
    }
}
