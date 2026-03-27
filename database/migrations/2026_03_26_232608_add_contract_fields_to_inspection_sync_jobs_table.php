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
            $table->string('contract_name')->default('inspection-sync')->after('operation');
            $table->unsignedInteger('contract_version')->default(1)->after('contract_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspection_sync_jobs', function (Blueprint $table) {
            $table->dropColumn(['contract_name', 'contract_version']);
        });
    }
};
