<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subject_type', 40)->index();
            $table->char('subject_key_hash', 64)->index();
            $table->string('category', 32)->index();
            $table->boolean('decision');
            $table->string('policy_version', 32);
            $table->string('source', 120)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_key_hash', 'category', 'recorded_at'], 'consent_subject_category_index');
        });

        Schema::create('newsletter_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('email');
            $table->string('normalized_email');
            $table->string('locale', 12);
            $table->string('status', 24)->default('pending')->index();
            $table->char('confirmation_token_hash', 64)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source', 80)->nullable();
            $table->timestamps();
            $table->unique(['normalized_email', 'locale'], 'newsletter_email_locale_unique');
        });

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

        Schema::create('redirects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('source_path', 768)->unique();
            $table->string('destination_url', 1024);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamps();
        });

        Schema::create('search_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('searchable_type', 80);
            $table->uuid('searchable_id');
            $table->string('locale', 12);
            $table->string('title', 220);
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->string('url', 1024);
            $table->json('filters')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['searchable_type', 'searchable_id', 'locale'], 'search_document_subject_unique');
            $table->index(['locale', 'searchable_type', 'published_at'], 'search_public_filter_index');
        });

        Schema::create('search_synonyms', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('locale', 12);
            $table->string('source', 160);
            $table->string('target', 160);
            $table->timestamps();
            $table->unique(['locale', 'source', 'target'], 'search_synonym_unique');
        });

        Schema::create('search_query_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('locale', 12);
            $table->char('query_hash', 64);
            $table->unsignedInteger('result_count');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['locale', 'created_at'], 'search_query_locale_created_index');
        });

        Schema::create('analytics_event_outbox', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event', 100);
            $table->json('payload');
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('available_at')->useCurrent()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_event_outbox');
        Schema::dropIfExists('search_query_logs');
        Schema::dropIfExists('search_synonyms');
        Schema::dropIfExists('search_documents');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('seo_metadata');
        Schema::dropIfExists('newsletter_subscriptions');
        Schema::dropIfExists('consent_records');
    }
};
