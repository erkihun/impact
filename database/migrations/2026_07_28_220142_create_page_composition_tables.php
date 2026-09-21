<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key', 80)->unique();
            $table->string('type', 40)->index();
            $table->string('name', 160);
            $table->json('definition');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('page_compositions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('template_id')->nullable()->constrained('page_templates')->nullOnDelete();
            $table->foreignUuid('based_on_id')->nullable()->constrained('page_compositions')->nullOnDelete();
            $table->string('page_key', 160);
            $table->string('locale', 12)->index();
            $table->string('template_type', 40)->index();
            $table->string('state', 24)->default('draft')->index();
            $table->unsignedInteger('version_no')->default(1);
            $table->unsignedInteger('lock_version')->default(1);
            $table->char('content_hash', 64);
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('publish_at')->nullable()->index();
            $table->timestamp('unpublish_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['page_key', 'locale', 'version_no'], 'page_composition_version_unique');
            $table->index(['page_key', 'locale', 'state'], 'page_composition_public_index');
        });

        Schema::create('page_sections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('page_composition_id')->constrained()->cascadeOnDelete();
            $table->uuid('current_version_id')->nullable();
            $table->string('stable_key', 100);
            $table->string('editor_label', 160);
            $table->unsignedInteger('sort_order');
            $table->boolean('required')->default(false);
            $table->boolean('locked')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['page_composition_id', 'stable_key'], 'page_section_stable_key_unique');
            $table->unique(['page_composition_id', 'sort_order'], 'page_section_order_unique');
        });

        Schema::create('page_section_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('page_section_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->string('locale', 12)->index();
            $table->string('type', 60)->index();
            $table->string('variant', 60);
            $table->json('content');
            $table->json('presentation');
            $table->boolean('enabled')->default(true);
            $table->string('visibility_rule', 32)->default('always');
            $table->timestamp('visible_from')->nullable()->index();
            $table->timestamp('visible_until')->nullable()->index();
            $table->unsignedSmallInteger('maximum_items')->nullable();
            $table->string('selection_mode', 32)->nullable();
            $table->char('content_hash', 64);
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['page_section_id', 'version_no'], 'page_section_version_unique');
        });

        Schema::table('page_sections', function (Blueprint $table): void {
            $table->foreign('current_version_id')
                ->references('id')->on('page_section_versions')->nullOnDelete();
        });

        Schema::create('page_section_relations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('page_section_version_id')->constrained()->cascadeOnDelete();
            $table->string('relation_type', 60);
            $table->uuidMorphs('related');
            $table->unsignedInteger('sort_order');
            $table->json('metadata')->nullable();
            $table->unique(
                ['page_section_version_id', 'relation_type', 'related_type', 'related_id'],
                'page_section_relation_unique',
            );
        });

        Schema::create('page_section_media', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('page_section_version_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('media_asset_id')->constrained()->restrictOnDelete();
            $table->string('role', 40)->default('primary');
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('focal_x', 5, 4)->nullable();
            $table->decimal('focal_y', 5, 4)->nullable();
            $table->boolean('decorative')->default(false);
            $table->text('caption')->nullable();
            $table->unique(
                ['page_section_version_id', 'media_asset_id', 'role'],
                'page_section_media_unique',
            );
        });

        Schema::create('page_section_actions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('page_section_version_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120);
            $table->string('action_type', 32);
            $table->string('internal_route', 160)->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->nullableUuidMorphs('destination');
            $table->string('button_variant', 24);
            $table->string('accessible_description', 255)->nullable();
            $table->boolean('open_new_context')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('page_composition_workflow_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('page_composition_id')->constrained()->cascadeOnDelete();
            $table->string('from_state', 32);
            $table->string('to_state', 32)->index();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->uuid('correlation_id')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['page_composition_id', 'created_at'], 'page_composition_workflow_timeline');
        });

        Schema::create('page_navigation_configurations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->nullable()
                ->constrained('page_navigation_configurations')->cascadeOnDelete();
            $table->string('location', 32)->index();
            $table->string('locale', 12)->index();
            $table->string('label', 120);
            $table->string('description', 255)->nullable();
            $table->string('route_name', 160);
            $table->json('route_parameters')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('enabled')->default(true)->index();
            $table->timestamp('visible_from')->nullable();
            $table->timestamp('visible_until')->nullable();
            $table->timestamps();
            $table->index(['location', 'locale', 'enabled', 'sort_order'], 'page_navigation_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_navigation_configurations');
        Schema::dropIfExists('page_composition_workflow_events');
        Schema::dropIfExists('page_section_actions');
        Schema::dropIfExists('page_section_media');
        Schema::dropIfExists('page_section_relations');
        Schema::table('page_sections', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('page_section_versions');
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('page_compositions');
        Schema::dropIfExists('page_templates');
    }
};
