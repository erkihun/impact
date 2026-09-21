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
        Schema::table('newsletter_subscriptions', function (Blueprint $table) {
            $table->foreignUuid('consent_record_id')
                ->nullable()
                ->after('locale')
                ->constrained('consent_records')
                ->restrictOnDelete();
            $table->string('policy_version', 32)->default('legacy')->after('consent_record_id');
            $table->char('unsubscribe_token_hash', 64)->nullable()->unique()->after('confirmation_token_hash');
            $table->timestamp('confirmation_sent_at')->nullable()->after('unsubscribe_token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consent_record_id');
            $table->dropUnique(['unsubscribe_token_hash']);
            $table->dropColumn([
                'policy_version',
                'unsubscribe_token_hash',
                'confirmation_sent_at',
            ]);
        });
    }
};
