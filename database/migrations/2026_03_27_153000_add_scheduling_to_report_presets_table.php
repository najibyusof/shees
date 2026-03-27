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
        Schema::table('report_presets', function (Blueprint $table) {
            $table->string('export_format', 10)->default('csv')->after('module');
            $table->boolean('schedule_enabled')->default(false)->after('filters');
            $table->string('schedule_frequency', 20)->nullable()->after('schedule_enabled');
            $table->string('schedule_time', 5)->nullable()->after('schedule_frequency');
            $table->timestamp('next_run_at')->nullable()->after('schedule_time');
            $table->timestamp('last_run_at')->nullable()->after('next_run_at');

            $table->index(['schedule_enabled', 'next_run_at'], 'report_presets_schedule_next_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_presets', function (Blueprint $table) {
            $table->dropIndex('report_presets_schedule_next_idx');
            $table->dropColumn([
                'export_format',
                'schedule_enabled',
                'schedule_frequency',
                'schedule_time',
                'next_run_at',
                'last_run_at',
            ]);
        });
    }
};
