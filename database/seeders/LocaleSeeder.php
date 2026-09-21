<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Locale;
use Illuminate\Database\Seeder;

final class LocaleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true, 'sort_order' => 1],
            ['code' => 'am', 'name' => 'Amharic', 'native_name' => 'አማርኛ', 'is_default' => false, 'sort_order' => 2],
        ] as $locale) {
            Locale::query()->updateOrCreate(
                ['code' => $locale['code']],
                [...$locale, 'direction' => 'ltr', 'enabled' => true],
            );
        }
    }
}
