<?php

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
        Schema::table('mobile_access_tokens', function (Blueprint $table) {
            $table->foreignId('replaced_by_token_id')
                ->nullable()
                ->after('is_active')
                ->constrained('mobile_access_tokens')
                ->nullOnDelete();
            $table->timestamp('rotated_at')->nullable()->after('last_used_at');
            $table->timestamp('revoked_at')->nullable()->after('rotated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobile_access_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaced_by_token_id');
            $table->dropColumn(['rotated_at', 'revoked_at']);
        });
    }
};
