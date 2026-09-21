<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->longText('cover_letter_encrypted')->nullable()->after('phone_encrypted');
        });

        Schema::create('application_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('media_asset_id')->unique()->constrained()->restrictOnDelete();
            $table->string('classification', 24);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'classification'], 'application_file_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_files');
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn('cover_letter_encrypted');
        });
    }
};
