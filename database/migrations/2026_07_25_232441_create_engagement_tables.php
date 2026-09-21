<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->restrictOnDelete();
            $table->string('name', 160);
            $table->string('email');
            $table->string('normalized_email');
            $table->string('status', 24)->default('confirmed')->index();
            $table->char('confirmation_token_hash', 64)->nullable();
            $table->uuid('consent_record_id')->nullable();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->unique(['event_id', 'normalized_email'], 'event_registration_email_unique');
        });

        Schema::create('applications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('vacancy_id')->constrained()->restrictOnDelete();
            $table->string('reference_no', 40)->unique();
            $table->string('applicant_name', 160);
            $table->string('email')->index();
            $table->text('phone_encrypted')->nullable();
            $table->string('status', 24)->default('received')->index();
            $table->date('retention_until')->index();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            $table->index(['vacancy_id', 'status', 'submitted_at'], 'application_review_queue_index');
        });

        Schema::create('application_status_histories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->uuid('correlation_id');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('engagement_submissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference_no', 40)->unique();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->string('type', 24)->index();
            $table->string('status', 24)->default('received')->index();
            $table->string('locale', 12);
            $table->string('contact_name', 160);
            $table->string('organization_name', 200)->nullable();
            $table->string('role', 160)->nullable();
            $table->string('email')->index();
            $table->text('phone_encrypted')->nullable();
            $table->foreignUuid('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignUuid('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->text('description_encrypted');
            $table->string('timeframe', 100)->nullable();
            $table->string('budget_range', 100)->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('retention_until')->index();
            $table->timestamp('submitted_at')->useCurrent()->index();
            $table->timestamps();
            $table->index(['status', 'assigned_to', 'submitted_at'], 'submission_owner_queue_index');
        });

        Schema::create('submission_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')->constrained('engagement_submissions')->restrictOnDelete();
            $table->foreignUuid('media_asset_id')->unique()->constrained()->restrictOnDelete();
            $table->string('classification', 24);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('offices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('status', 24)->default('active')->index();
            $table->boolean('is_primary')->default(false);
            $table->string('locale', 12)->default('en');
            $table->string('name', 160);
            $table->text('address');
            $table->string('city', 120);
            $table->string('country', 120);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('hours', 255)->nullable();
            $table->string('timezone', 64);
            $table->string('directions_url', 1024)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offices');
        Schema::dropIfExists('submission_files');
        Schema::dropIfExists('engagement_submissions');
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('event_registrations');
    }
};
