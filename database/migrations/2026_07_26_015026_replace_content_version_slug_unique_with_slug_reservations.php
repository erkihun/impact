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
        Schema::create('content_slugs', function (Blueprint $table): void {
            $table->foreignUuid('content_item_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('slug', 200);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['locale', 'slug'], 'content_slug_locale_unique');
            $table->index(['content_item_id', 'locale'], 'content_slug_item_locale_index');
        });

        DB::table('content_versions')
            ->select(['content_item_id', 'locale', 'slug'])
            ->distinct()
            ->orderBy('content_item_id')
            ->chunk(500, function ($versions): void {
                foreach ($versions as $version) {
                    DB::table('content_slugs')->insertOrIgnore([
                        'content_item_id' => $version->content_item_id,
                        'locale' => $version->locale,
                        'slug' => $version->slug,
                        'created_at' => now('UTC'),
                    ]);
                }
            });

        Schema::table('content_versions', function (Blueprint $table): void {
            $table->dropUnique('content_locale_slug_unique');
            $table->index(['locale', 'slug'], 'content_version_locale_slug_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_versions', function (Blueprint $table): void {
            $table->dropIndex('content_version_locale_slug_index');
            $table->unique(['locale', 'slug'], 'content_locale_slug_unique');
        });
        Schema::dropIfExists('content_slugs');
    }
};
