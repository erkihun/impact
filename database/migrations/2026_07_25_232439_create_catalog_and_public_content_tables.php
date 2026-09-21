<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('status', 24)->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('featured')->default(false);
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('service_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id')->constrained()->restrictOnDelete();
            $table->string('locale', 12);
            $table->unsignedInteger('version_no')->default(1);
            $table->string('slug', 200);
            $table->string('name', 160);
            $table->text('summary')->nullable();
            $table->text('problem_statement')->nullable();
            $table->longText('approach')->nullable();
            $table->json('deliverables');
            $table->text('benefits')->nullable();
            $table->string('cta_label', 120)->nullable();
            $table->string('workflow_state', 24)->default('draft')->index();
            $table->timestamps();
            $table->unique(['service_id', 'locale', 'version_no'], 'service_version_sequence_unique');
            $table->unique(['locale', 'slug'], 'service_locale_slug_unique');
        });

        Schema::create('industries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('status', 24)->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });

        Schema::create('industry_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('industry_id')->constrained()->restrictOnDelete();
            $table->string('locale', 12);
            $table->unsignedInteger('version_no')->default(1);
            $table->string('slug', 200);
            $table->string('name', 160);
            $table->text('summary')->nullable();
            $table->longText('overview')->nullable();
            $table->longText('challenges')->nullable();
            $table->string('workflow_state', 24)->default('draft')->index();
            $table->timestamps();
            $table->unique(['industry_id', 'locale', 'version_no'], 'industry_version_sequence_unique');
            $table->unique(['locale', 'slug'], 'industry_locale_slug_unique');
        });

        Schema::create('experts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('draft')->index();
            $table->boolean('public_email_enabled')->default(false);
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->foreignUuid('profile_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->dateTimeTz('publication_authorized_at')->nullable();
            $table->string('authorization_reference', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('expert_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('expert_id')->constrained()->restrictOnDelete();
            $table->string('locale', 12);
            $table->unsignedInteger('version_no')->default(1);
            $table->string('slug', 200);
            $table->string('display_name', 160);
            $table->string('professional_title', 180);
            $table->longText('biography');
            $table->json('qualifications')->nullable();
            $table->json('languages')->nullable();
            $table->string('workflow_state', 24)->default('draft')->index();
            $table->timestamps();
            $table->unique(['expert_id', 'locale', 'version_no'], 'expert_version_sequence_unique');
            $table->unique(['locale', 'slug'], 'expert_locale_slug_unique');
        });

        Schema::create('case_studies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('status', 24)->default('draft')->index();
            $table->string('client_display_mode', 20);
            $table->dateTimeTz('client_consent_at')->nullable();
            $table->string('authorization_reference', 255)->nullable();
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });

        Schema::create('case_study_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_study_id')->constrained()->restrictOnDelete();
            $table->string('locale', 12);
            $table->unsignedInteger('version_no')->default(1);
            $table->string('slug', 200);
            $table->string('title', 220);
            $table->string('client_name', 200)->nullable();
            $table->longText('challenge');
            $table->longText('approach');
            $table->longText('solution')->nullable();
            $table->longText('deliverables')->nullable();
            $table->longText('outcomes');
            $table->longText('value_created')->nullable();
            $table->json('metrics')->nullable();
            $table->string('workflow_state', 24)->default('draft')->index();
            $table->timestamps();
            $table->unique(['case_study_id', 'locale', 'version_no'], 'case_version_sequence_unique');
            $table->unique(['locale', 'slug'], 'case_locale_slug_unique');
        });

        Schema::create('insights', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 30)->index();
            $table->string('status', 24)->default('draft')->index();
            $table->boolean('featured')->default(false);
            $table->dateTimeTz('published_at')->nullable()->index();
            $table->dateTimeTz('expires_at')->nullable();
            $table->foreignUuid('primary_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('insight_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('insight_id')->constrained()->restrictOnDelete();
            $table->string('locale', 12);
            $table->unsignedInteger('version_no')->default(1);
            $table->string('slug', 200);
            $table->string('title', 220);
            $table->text('excerpt');
            $table->longText('body')->nullable();
            $table->string('canonical_source', 1024)->nullable();
            $table->string('workflow_state', 24)->default('draft')->index();
            $table->timestamps();
            $table->unique(['insight_id', 'locale', 'version_no'], 'insight_version_sequence_unique');
            $table->unique(['locale', 'slug'], 'insight_locale_slug_unique');
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('status', 24)->default('draft')->index();
            $table->string('format', 24)->default('physical');
            $table->string('title', 220);
            $table->string('slug', 200);
            $table->string('locale', 12);
            $table->longText('description');
            $table->dateTimeTz('starts_at')->index();
            $table->dateTimeTz('ends_at');
            $table->string('timezone', 64);
            $table->string('venue', 255)->nullable();
            $table->text('meeting_url_encrypted')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->dateTimeTz('registration_closes_at')->nullable();
            $table->timestamps();
            $table->unique(['locale', 'slug'], 'event_locale_slug_unique');
        });

        Schema::create('vacancies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference_no', 40)->unique();
            $table->string('status', 24)->default('draft')->index();
            $table->string('title', 220);
            $table->string('slug', 200);
            $table->string('locale', 12);
            $table->string('type', 40);
            $table->string('location', 160)->nullable();
            $table->longText('description');
            $table->longText('requirements');
            $table->dateTimeTz('opens_at')->nullable();
            $table->dateTimeTz('closes_at')->nullable()->index();
            $table->unsignedInteger('application_limit')->nullable();
            $table->timestamps();
            $table->unique(['locale', 'slug'], 'vacancy_locale_slug_unique');
        });

        Schema::create('service_industry', function (Blueprint $table): void {
            $table->foreignUuid('service_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('industry_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('featured')->default(false);
            $table->primary(['service_id', 'industry_id']);
        });

        Schema::create('expert_service', function (Blueprint $table): void {
            $table->foreignUuid('expert_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('service_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('featured')->default(false);
            $table->primary(['expert_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_service');
        Schema::dropIfExists('service_industry');
        Schema::dropIfExists('vacancies');
        Schema::dropIfExists('events');
        Schema::dropIfExists('insight_versions');
        Schema::dropIfExists('insights');
        Schema::dropIfExists('case_study_versions');
        Schema::dropIfExists('case_studies');
        Schema::dropIfExists('expert_versions');
        Schema::dropIfExists('experts');
        Schema::dropIfExists('industry_versions');
        Schema::dropIfExists('industries');
        Schema::dropIfExists('service_versions');
        Schema::dropIfExists('services');
    }
};
