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
        Schema::create('inspection_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_checklist_item_id')->constrained()->cascadeOnDelete();
            $table->uuid('offline_uuid')->unique();
            $table->text('response_value')->nullable();
            $table->json('response_meta')->nullable();
            $table->boolean('is_non_compliant')->default(false);
            $table->text('comment')->nullable();
            $table->string('sync_status')->default('pending_sync');
            $table->string('sync_batch_uuid')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['inspection_id', 'inspection_checklist_item_id'], 'inspection_response_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_responses');
    }
};
