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
        Schema::table('inspection_sync_jobs', function (Blueprint $table) {
            $table->string('idempotency_key', 128)->nullable()->after('sync_batch_uuid');
            $table->timestamp('processing_started_at')->nullable()->after('received_at');
            $table->timestamp('processing_finished_at')->nullable()->after('processed_at');
            $table->unsignedInteger('processing_latency_ms')->nullable()->after('processing_finished_at');
            $table->unsignedInteger('retry_count')->default(0)->after('processing_latency_ms');

            $table->unique(['user_id', 'device_identifier', 'idempotency_key'], 'inspection_sync_jobs_user_device_idempotency_unique');
            $table->index('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspection_sync_jobs', function (Blueprint $table) {
            $table->dropUnique('inspection_sync_jobs_user_device_idempotency_unique');
            $table->dropIndex(['idempotency_key']);
            $table->dropColumn([
                'idempotency_key',
                'processing_started_at',
                'processing_finished_at',
                'processing_latency_ms',
                'retry_count',
            ]);
        });
    }
};
