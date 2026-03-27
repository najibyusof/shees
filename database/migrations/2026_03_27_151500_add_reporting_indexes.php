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
        Schema::table('incidents', function (Blueprint $table) {
            $table->index(['status', 'reported_by', 'datetime'], 'incidents_status_reported_datetime_idx');
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->index(['starts_at', 'is_active'], 'trainings_starts_active_idx');
            $table->index(['is_active', 'created_at'], 'trainings_active_created_idx');
        });

        Schema::table('site_audits', function (Blueprint $table) {
            $table->index(['created_by', 'status', 'scheduled_for'], 'site_audits_creator_status_sched_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex('incidents_status_reported_datetime_idx');
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->dropIndex('trainings_starts_active_idx');
            $table->dropIndex('trainings_active_created_idx');
        });

        Schema::table('site_audits', function (Blueprint $table) {
            $table->dropIndex('site_audits_creator_status_sched_idx');
        });
    }
};
