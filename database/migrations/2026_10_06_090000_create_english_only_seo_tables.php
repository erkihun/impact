<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-language SEO storage.
 *
 * The original seo_metadata table was keyed by locale and never consumed by
 * the application, so it is replaced rather than altered. Overrides are now
 * attached to a stable subject (a static page key or a resource identity) so
 * they survive content versioning.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('seo_metadata');

        Schema::create('seo_metadata', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subject_type', 40);
            $table->string('subject_key', 80);
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('canonical_path', 768)->nullable();
            $table->string('robots', 40)->nullable();
            $table->boolean('include_in_sitemap')->default(true);
            $table->string('social_title', 180)->nullable();
            $table->string('social_description', 320)->nullable();
            $table->foreignUuid('social_image_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('primary_topic', 120)->nullable();
            $table->json('secondary_topics')->nullable();
            $table->string('target_audience', 160)->nullable();
            $table->string('search_intent', 24)->nullable();
            $table->json('geographic_relevance')->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['subject_type', 'subject_key'], 'seo_subject_unique');
        });

        Schema::table('redirects', function (Blueprint $table): void {
            $table->string('destination_url', 1024)->nullable()->change();
            $table->string('origin', 24)->default('manual')->index();
            $table->string('subject_type', 40)->nullable();
            $table->string('subject_key', 80)->nullable();
            $table->string('reason', 255)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_hit_at')->nullable();
            $table->index(['subject_type', 'subject_key'], 'redirect_subject_index');
        });

        Schema::create('seo_link_checks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('fingerprint', 64)->unique();
            $table->string('url', 1024);
            $table->string('source_url', 1024);
            $table->string('source_label', 160);
            $table->string('link_text', 255)->nullable();
            $table->boolean('external')->default(false);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('result', 24)->index();
            $table->string('severity', 24)->index();
            $table->string('resolution_status', 24)->default('open')->index();
            $table->timestamp('first_detected_at')->useCurrent();
            $table->timestamp('last_checked_at')->useCurrent();
            $table->timestamps();
        });

        // Editor-curated contextual links between public resources, used for
        // "related" sections alongside the existing service/industry and
        // expert/service pivots. Identities are stable resource ids.
        Schema::create('content_relations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('source_type', 40);
            $table->uuid('source_id');
            $table->string('target_type', 40);
            $table->uuid('target_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['source_type', 'source_id', 'target_type', 'target_id'], 'content_relation_unique');
            $table->index(['target_type', 'target_id'], 'content_relation_target_index');
        });

        Schema::create('seo_audit_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedInteger('blocking_count');
            $table->unsignedInteger('warning_count');
            $table->unsignedInteger('information_count');
            $table->json('metrics');
            $table->json('issues');
            $table->string('trigger', 24);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_audit_runs');
        Schema::dropIfExists('content_relations');
        Schema::dropIfExists('seo_link_checks');

        Schema::table('redirects', function (Blueprint $table): void {
            $table->dropIndex('redirect_subject_index');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['origin', 'subject_type', 'subject_key', 'reason', 'last_hit_at']);
        });

        Schema::dropIfExists('seo_metadata');
        Schema::create('seo_metadata', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('seoable');
            $table->string('locale', 12);
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('canonical_url', 1024)->nullable();
            $table->string('robots', 80)->nullable();
            $table->json('structured_data')->nullable();
            $table->timestamps();
            $table->unique(['seoable_type', 'seoable_id', 'locale'], 'seo_subject_locale_unique');
        });
    }
};
