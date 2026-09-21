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
        Schema::create('content_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 40)->index();
            $table->foreignUuid('owner_id')->constrained('users')->restrictOnDelete();
            $table->uuid('current_version_id')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'status', 'published_at'], 'content_publication_index');
        });

        Schema::create('content_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_item_id')->constrained()->restrictOnDelete();
            $table->string('locale', 12)->index();
            $table->unsignedInteger('version_no');
            $table->string('slug', 200);
            $table->string('title', 220);
            $table->text('summary')->nullable();
            $table->json('body');
            $table->string('workflow_state', 24)->default('draft')->index();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->char('content_hash', 64);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['content_item_id', 'locale', 'version_no'], 'content_version_sequence_unique');
            $table->unique(['locale', 'slug'], 'content_locale_slug_unique');
        });

        Schema::create('workflow_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_item_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('content_version_id')->constrained()->restrictOnDelete();
            $table->string('from_state', 24)->nullable();
            $table->string('to_state', 24);
            $table->foreignUuid('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->uuid('correlation_id')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['content_item_id', 'created_at'], 'workflow_item_created_index');
        });

        Schema::create('publication_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_item_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('content_version_id')->constrained()->restrictOnDelete();
            $table->timestamp('publish_at')->index();
            $table->timestamp('unpublish_at')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['content_item_id', 'status'], 'publication_item_active_status_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_schedules');
        Schema::dropIfExists('workflow_events');
        Schema::dropIfExists('content_versions');
        Schema::dropIfExists('content_items');
    }
};
