<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\SettingCatalog;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SettingCatalog::DEFAULT_VALUES as $key => $value) {
            $setting = [
                'type' => SettingCatalog::DEFINITIONS[$key]['type'],
                'value' => $value,
            ];

            Setting::query()->updateOrCreate(['key' => $key, 'scope' => 'global'], $setting);
        }
    }
}
