<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use App\Support\RolePermissionMap;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleCode::cases() as $roleCode) {
            $role = Role::query()->updateOrCreate(
                ['code' => $roleCode->value],
                [
                    'name' => str($roleCode->value)->replace('_', ' ')->title()->toString(),
                    'is_system' => true,
                ],
            );
            $codes = $roleCode === RoleCode::SuperAdministrator
                ? array_keys(PermissionCatalog::ALL)
                : RolePermissionMap::MAP[$roleCode->value];

            $role->permissions()->sync(
                Permission::query()->whereIn('code', $codes)->pluck('id')->all(),
            );
        }
    }
}
