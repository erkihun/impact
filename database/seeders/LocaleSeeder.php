<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Locale;
use Illuminate\Database\Seeder;

final class LocaleSeeder extends Seeder
{
    public function run(): void
    {
        Locale::query()->where('code', '!=', 'en')->update(['enabled' => false, 'is_default' => false]);
        foreach ([
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true, 'sort_order' => 1],
        ] as $locale) {
            Locale::query()->updateOrCreate(
                ['code' => $locale['code']],
                [...$locale, 'direction' => 'ltr', 'enabled' => true],
            );
        }
    }
}
