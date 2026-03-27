<?php

namespace App\Console\Commands;

use App\Services\TrainingService;
use Illuminate\Console\Command;

class NotifyExpiringCertificatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trainings:notify-expiring-certificates {--days=30 : Days ahead to check certificate expiry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users about training certificates nearing expiry';

    /**
     * Execute the console command.
     */
    public function handle(TrainingService $trainingService): int
    {
        $days = (int) $this->option('days');
        $count = $trainingService->notifyExpiringCertificates($days);

        $this->info('Expiry notifications sent: '.$count);

        return self::SUCCESS;
    }
}
