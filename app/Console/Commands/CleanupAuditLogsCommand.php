<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CleanupAuditLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:cleanup-logs {--days=180 : Delete audit logs older than this number of days} {--dry-run : Show how many records would be deleted without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old audit log records based on retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = max(30, (int) $this->option('days'));
        $threshold = now()->subDays($days);

        $query = AuditLog::query()->where('created_at', '<', $threshold);
        $count = (clone $query)->count();

        if ((bool) $this->option('dry-run')) {
            $this->storeCleanupStatus([
                'last_run_at' => now()->toIso8601String(),
                'deleted_count' => 0,
                'days' => $days,
                'dry_run' => true,
                'eligible_count' => $count,
            ]);

            $this->info('Audit logs eligible for cleanup: '.$count);

            return self::SUCCESS;
        }

        $deleted = 0;

        $query->orderBy('id')->chunkById(1000, function ($logs) use (&$deleted) {
            $ids = $logs->pluck('id')->all();
            $deleted += AuditLog::query()->whereIn('id', $ids)->delete();
        });

        $this->storeCleanupStatus([
            'last_run_at' => now()->toIso8601String(),
            'deleted_count' => $deleted,
            'days' => $days,
            'dry_run' => false,
            'eligible_count' => $count,
        ]);

        $this->info('Audit logs removed: '.$deleted);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeCleanupStatus(array $payload): void
    {
        Cache::forever('audit_logs_cleanup_status', $payload);
    }
}
