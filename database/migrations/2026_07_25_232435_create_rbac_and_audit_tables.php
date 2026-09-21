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
        Schema::create('roles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('code', 80)->unique();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 120)->unique();
            $table->string('description', 255);
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignUuid('permission_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('role_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignUuid('role_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('audit_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 120);
            $table->nullableUuidMorphs('auditable');
            $table->char('before_hash', 64)->nullable();
            $table->char('after_hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('correlation_id');
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['action', 'created_at'], 'audit_action_created_index');
            $table->index(['actor_id', 'created_at'], 'audit_actor_created_index');
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'audit_subject_created_index');
        });

        Schema::create('security_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 120);
            $table->string('outcome', 40);
            $table->json('metadata')->nullable();
            $table->uuid('correlation_id')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['event', 'created_at'], 'security_event_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
