<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
            $table->foreignId('submitted_by')->nullable()->after('reported_by')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('submitted_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('reviewed_at');
            $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });

        DB::table('incidents')->where('status', 'Open')->update(['status' => 'draft']);
        DB::table('incidents')->where('status', 'Investigating')->update(['status' => 'under_review']);
        DB::table('incidents')->where('status', 'Resolved')->update(['status' => 'approved']);
        DB::table('incidents')->where('status', 'Closed')->update(['status' => 'rejected']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn([
                'submitted_at',
                'reviewed_at',
                'approved_at',
                'rejected_at',
                'rejection_reason',
            ]);

            $table->string('status')->default('Open')->change();
        });

        DB::table('incidents')->where('status', 'draft')->update(['status' => 'Open']);
        DB::table('incidents')->where('status', 'submitted')->update(['status' => 'Open']);
        DB::table('incidents')->where('status', 'under_review')->update(['status' => 'Investigating']);
        DB::table('incidents')->where('status', 'approved')->update(['status' => 'Resolved']);
        DB::table('incidents')->where('status', 'rejected')->update(['status' => 'Closed']);
    }
};
