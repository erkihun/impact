<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('locales')->where('code', '!=', 'en')->update(['enabled' => false, 'is_default' => false]);
        DB::table('locales')->where('code', 'en')->update(['enabled' => true, 'is_default' => true]);
        DB::table('users')->where('locale', '!=', 'en')->update(['locale' => 'en']);
        DB::table('user_invitations')->where('locale', '!=', 'en')->update(['locale' => 'en']);
        DB::table('settings')->whereIn('key', [
            'localization.default_locale', 'localization.fallback_locale',
            'localization.enabled_locales', 'email.default_locale', 'engagement.default_submission_locale',
        ])->update(['value' => json_encode('en', JSON_THROW_ON_ERROR)]);
    }

    public function down(): void
    {
        // Content translations are retained. Previous personal preferences cannot
        // be reconstructed, so rolling back does not guess those values.
    }
};
