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
        Schema::table('publication_schedules', function (Blueprint $table): void {
            $table->index(['content_item_id', 'status'], 'publication_item_status_index');
        });
        Schema::table('publication_schedules', function (Blueprint $table): void {
            $table->dropUnique('publication_item_active_status_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_schedules', function (Blueprint $table): void {
            $table->unique(['content_item_id', 'status'], 'publication_item_active_status_unique');
        });
        Schema::table('publication_schedules', function (Blueprint $table): void {
            $table->dropIndex('publication_item_status_index');
        });
    }
};
