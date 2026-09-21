<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_navigation_configurations', function (Blueprint $table): void {
            $table->string('icon', 60)->nullable()->after('description');
            $table->unsignedInteger('version_no')->default(1)->after('sort_order');
            $table->unsignedInteger('lock_version')->default(1)->after('version_no');
            $table->foreignUuid('updated_by')->nullable()->after('enabled')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->after('visible_until')->index();
        });
    }

    public function down(): void
    {
        Schema::table('page_navigation_configurations', function (Blueprint $table): void {
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['icon', 'version_no', 'lock_version', 'updated_by', 'published_at']);
        });
    }
};
