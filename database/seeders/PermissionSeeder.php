<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionCatalog::ALL as $code => $description) {
            Permission::query()->updateOrCreate(['code' => $code], ['description' => $description]);
        }
    }
}
