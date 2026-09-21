<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->unsignedInteger('version')->default(1)->after('scope');
            $table->string('change_reason', 500)->nullable()->after('updated_by');
            $table->timestamp('last_effective_at')->nullable()->after('change_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn(['version', 'change_reason', 'last_effective_at']);
        });
    }
};
