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
        Schema::create('inspection_sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_identifier')->nullable();
            $table->string('direction')->default('upload');
            $table->string('entity_type');
            $table->string('entity_offline_uuid')->nullable();
            $table->string('operation')->default('upsert');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->string('sync_batch_uuid')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'direction']);
            $table->index(['device_identifier', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_sync_jobs');
    }
};
