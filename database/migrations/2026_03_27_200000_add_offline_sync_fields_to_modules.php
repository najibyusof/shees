<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add offline sync columns to incidents
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('temporary_id', 36)->nullable()->unique()->after('id')
                ->comment('Client-assigned UUID for offline-created records');
            $table->timestamp('local_created_at')->nullable()->after('created_at')
                ->comment('Device-local timestamp when the record was created offline');
        });

        // Add offline sync columns to attendance_logs
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->string('temporary_id', 36)->nullable()->unique()->after('id')
                ->comment('Client-assigned UUID for offline-created attendance events');
            $table->timestamp('local_created_at')->nullable()->after('created_at')
                ->comment('Device-local timestamp when the attendance event was recorded offline');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropUnique(['temporary_id']);
            $table->dropColumn(['temporary_id', 'local_created_at']);
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropUnique(['temporary_id']);
            $table->dropColumn(['temporary_id', 'local_created_at']);
        });
    }
};
