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
        Schema::create('legal_holds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('holdable');
            $table->text('reason');
            $table->foreignUuid('placed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('placed_at')->useCurrent();
            $table->foreignUuid('released_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->index(['holdable_type', 'holdable_id', 'released_at'], 'legal_hold_active_subject_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_holds');
    }
};
