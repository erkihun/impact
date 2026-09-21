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
        Schema::create('locales', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 12)->unique();
            $table->string('name', 80);
            $table->string('native_name', 80);
            $table->string('direction', 3)->default('ltr');
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key', 120);
            $table->string('type', 30);
            $table->json('value')->nullable();
            $table->string('scope', 30)->default('global');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['key', 'scope'], 'settings_key_scope_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('locales');
    }
};
