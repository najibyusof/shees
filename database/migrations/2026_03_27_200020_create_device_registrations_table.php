<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id')->index()->comment('Unique hardware/instance ID from the mobile app');
            $table->string('device_name');
            $table->string('platform', 20)->nullable()->comment('ios | android | web');
            $table->string('app_version', 50)->nullable();
            $table->string('push_token', 500)->nullable()->comment('FCM / APNs push token for future notifications');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            // A user can register the same device once
            $table->unique(['user_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_registrations');
    }
};
