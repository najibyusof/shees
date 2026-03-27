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
        Schema::create('inspection_sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_sync_job_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type');
            $table->string('entity_offline_uuid')->nullable();
            $table->string('conflict_type')->default('version_mismatch');
            $table->json('client_payload')->nullable();
            $table->json('server_payload')->nullable();
            $table->string('resolution_status')->default('open');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['resolution_status', 'entity_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_sync_conflicts');
    }
};
