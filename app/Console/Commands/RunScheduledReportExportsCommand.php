<?php

namespace App\Console\Commands;

use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\ReportPreset;
use Illuminate\Console\Command;

class RunScheduledReportExportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:run-scheduled-exports {--limit=50 : Maximum scheduled presets to process per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue report exports for presets that are due based on schedule settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $duePresets = ReportPreset::query()
            ->where('schedule_enabled', true)
            ->whereNotNull('schedule_frequency')
            ->where(function ($query) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get();

        $queued = 0;

        foreach ($duePresets as $preset) {
            $reportExport = ReportExport::query()->create([
                'user_id' => $preset->user_id,
                'module' => $preset->module,
                'format' => $preset->export_format ?: 'csv',
                'filters' => $preset->filters,
                'status' => 'queued',
            ]);

            GenerateReportExportJob::dispatch($reportExport->id);

            $preset->update([
                'last_run_at' => now(),
                'next_run_at' => $this->nextRunAt($preset->schedule_frequency, $preset->schedule_time),
            ]);

            $queued++;
        }

        $this->info('Scheduled report exports queued: '.$queued);

        return self::SUCCESS;
    }

    private function nextRunAt(?string $frequency, ?string $time): \Illuminate\Support\Carbon
    {
        $frequency = $frequency ?: 'daily';
        $time = $time ?: '07:00';

        $next = now()->copy();

        if ($frequency === 'weekly') {
            $next->addWeek();
        } else {
            $next->addDay();
        }

        return $next->setTimeFromTimeString($time);
    }
}
