<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_comments', function (Blueprint $table): void {
            $table->boolean('is_critical')->default(false)->after('comment_type');
            $table->boolean('is_resolved')->default(false)->after('is_critical');
            $table->foreignId('resolved_by')->nullable()->after('is_resolved')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            $table->text('resolution_note')->nullable()->after('resolved_at');

            $table->index(['incident_id', 'is_resolved'], 'incident_comments_incident_resolved_idx');
            $table->index(['incident_id', 'is_critical', 'is_resolved'], 'incident_comments_incident_critical_resolved_idx');
        });
    }

    public function down(): void
    {
        Schema::table('incident_comments', function (Blueprint $table): void {
            $table->dropIndex('incident_comments_incident_critical_resolved_idx');
            $table->dropIndex('incident_comments_incident_resolved_idx');

            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn(['is_critical', 'is_resolved', 'resolved_at', 'resolution_note']);
        });
    }
};
