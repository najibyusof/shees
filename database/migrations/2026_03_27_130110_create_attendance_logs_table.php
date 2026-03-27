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
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type')->default('ping');
            $table->timestamp('logged_at');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('speed_mps', 8, 2)->nullable();
            $table->decimal('heading_degrees', 6, 2)->nullable();
            $table->string('source')->default('simulated');
            $table->string('device_identifier')->nullable();
            $table->string('external_event_id')->nullable();
            $table->boolean('inside_geofence')->nullable();
            $table->unsignedInteger('distance_from_geofence_meters')->nullable();
            $table->string('alert_level')->nullable();
            $table->text('alert_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['worker_id', 'logged_at']);
            $table->index(['inside_geofence', 'logged_at']);
            $table->index(['source', 'device_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
