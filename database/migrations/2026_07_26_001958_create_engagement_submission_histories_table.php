<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagement_submission_histories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('engagement_submission_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 24);
            $table->string('to_status', 24);
            $table->foreignUuid('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->uuid('correlation_id')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['engagement_submission_id', 'created_at'], 'submission_history_timeline_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_submission_histories');
    }
};
