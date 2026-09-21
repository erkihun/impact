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
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('original_name');
            $table->string('disk', 40);
            $table->string('path', 768)->unique();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->string('visibility', 20)->index();
            $table->string('scan_status', 24)->default('quarantined')->index();
            $table->string('processing_status', 24)->default('quarantined')->index();
            $table->string('title', 220)->nullable();
            $table->string('alt_text', 500)->nullable();
            $table->string('credit', 255)->nullable();
            $table->string('copyright', 255)->nullable();
            $table->string('locale', 12)->nullable();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('retention_until')->nullable()->index();
            $table->timestamps();
            $table->index(['scan_status', 'processing_status', 'created_at'], 'media_worker_pickup_index');
        });

        Schema::create('media_variants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('media_asset_id')->constrained()->cascadeOnDelete();
            $table->string('variant', 60);
            $table->string('path', 768)->unique();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
            $table->unique(['media_asset_id', 'variant'], 'media_asset_variant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_variants');
        Schema::dropIfExists('media_assets');
    }
};
