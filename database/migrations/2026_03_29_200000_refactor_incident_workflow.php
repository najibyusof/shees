<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create incident_workflow_logs table for full audit trail
        Schema::create('incident_workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->string('from_status', 60);
            $table->string('to_status', 60);
            $table->string('action', 80);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['incident_id', 'created_at']);
        });

        // 2. Ensure incident_comment_replies table exists (may already exist from prior migration)
        if (! Schema::hasTable('incident_comment_replies')) {
            Schema::create('incident_comment_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('incident_comment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('reply');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Add comment_type to incident_comments if not already present
        if (! Schema::hasColumn('incident_comments', 'comment_type')) {
            Schema::table('incident_comments', function (Blueprint $table) {
                $table->string('comment_type', 40)->default('general')->after('comment');
            });
        }

        // 4. Add tagged_users to incident_comments (optional @mentions)
        if (! Schema::hasColumn('incident_comments', 'tagged_users')) {
            Schema::table('incident_comments', function (Blueprint $table) {
                $table->json('tagged_users')->nullable()->after('comment_type');
            });
        }

        // 5. Migrate existing status values to new workflow statuses
        DB::table('incidents')->where('status', 'submitted')->update(['status' => 'draft_submitted']);
        DB::table('incidents')->where('status', 'under_review')->update(['status' => 'draft_reviewed']);
        DB::table('incidents')->where('status', 'approved')->update(['status' => 'closed']);
        // rejected → draft: reset back so creator can address feedback and manager can re-submit
        DB::table('incidents')->where('status', 'rejected')->update(['status' => 'draft']);
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_workflow_logs');

        // Reverse status migration (best-effort)
        DB::table('incidents')->where('status', 'draft_submitted')->update(['status' => 'submitted']);
        DB::table('incidents')->where('status', 'draft_reviewed')->update(['status' => 'under_review']);
        DB::table('incidents')->where('status', 'final_submitted')->update(['status' => 'under_review']);
        DB::table('incidents')->where('status', 'final_reviewed')->update(['status' => 'under_review']);
        DB::table('incidents')->where('status', 'pending_closure')->update(['status' => 'under_review']);
        DB::table('incidents')->where('status', 'closed')->update(['status' => 'approved']);

        if (Schema::hasColumn('incident_comments', 'tagged_users')) {
            Schema::table('incident_comments', function (Blueprint $table) {
                $table->dropColumn('tagged_users');
            });
        }
    }
};
