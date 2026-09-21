<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('engagement_submission_histories', function (Blueprint $table): void {
            $table->string('from_status', 24)->nullable()->change();
            $table->uuid('actor_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('engagement_submission_histories')
            ->whereNull('from_status')
            ->whereNull('actor_id')
            ->delete();

        Schema::table('engagement_submission_histories', function (Blueprint $table): void {
            $table->string('from_status', 24)->nullable(false)->change();
            $table->uuid('actor_id')->nullable(false)->change();
        });
    }
};
