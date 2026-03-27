<?php

namespace App\Console\Commands;

use App\Models\ReportExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupReportExportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:cleanup-exports {--days=30 : Delete completed/failed exports older than this number of days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old report export records and stored files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = now()->subDays($days);
        $deleted = 0;

        ReportExport::query()
            ->whereIn('status', ['completed', 'failed'])
            ->where('created_at', '<', $threshold)
            ->orderBy('id')
            ->chunkById(200, function ($exports) use (&$deleted) {
                foreach ($exports as $export) {
                    if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
                        Storage::disk('local')->delete($export->file_path);
                    }

                    $export->delete();
                    $deleted++;
                }
            });

        $this->info('Old report exports removed: '.$deleted);

        return self::SUCCESS;
    }
}
