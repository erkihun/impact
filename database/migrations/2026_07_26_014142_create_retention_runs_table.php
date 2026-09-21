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
        Schema::create('retention_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('policy_version', 32);
            $table->string('mode', 24);
            $table->string('status', 32)->index();
            $table->unsignedInteger('candidate_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('legal_hold_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->json('failures')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->uuid('correlation_id')->index();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['mode', 'started_at'], 'retention_run_mode_started_index');
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->timestamp('retention_processed_at')->nullable()->index();
        });
        Schema::table('engagement_submissions', function (Blueprint $table): void {
            $table->timestamp('retention_processed_at')->nullable()->index();
        });
        Schema::table('event_registrations', function (Blueprint $table): void {
            $table->date('retention_until')->nullable()->index();
            $table->timestamp('retention_processed_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table): void {
            $table->dropColumn(['retention_until', 'retention_processed_at']);
        });
        Schema::table('engagement_submissions', function (Blueprint $table): void {
            $table->dropColumn('retention_processed_at');
        });
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn('retention_processed_at');
        });
        Schema::dropIfExists('retention_runs');
    }
};
