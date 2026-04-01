<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Throwable;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $allowDemoInProduction = (bool) env('ALLOW_DEMO_SEEDING', false);

        $seeders = [
            SystemBaselineSeeder::class,
        ];

        if (! app()->environment('production') || $allowDemoInProduction) {
            $seeders[] = DemoSampleDataSeeder::class;
        } elseif ($this->command) {
            $this->command->warn('Demo sample seeding disabled in production.');
            $this->command->line('Set ALLOW_DEMO_SEEDING=true to include demo seeders.');
        }

        $failed = [];

        foreach ($seeders as $seeder) {
            try {
                $this->call($seeder);
            } catch (Throwable $exception) {
                $failed[] = $seeder;

                Log::error('Seeder execution failed and was skipped.', [
                    'seeder' => $seeder,
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);

                if ($this->command) {
                    $this->command->warn("Skipped failing seeder: {$seeder}");
                    $this->command->line("Reason: {$exception->getMessage()}");
                }
            }
        }

        if ($failed !== [] && $this->command) {
            $this->command->warn('Completed with skipped seeders: '.implode(', ', $failed));
        }
    }
}
