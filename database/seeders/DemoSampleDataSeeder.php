<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSampleDataSeeder extends Seeder
{
    /**
     * Seed demo/sample data used for non-production showcases.
     */
    public function run(): void
    {
        $allowInProduction = (bool) env('ALLOW_DEMO_SEEDING', false);

        if (app()->environment('production') && ! $allowInProduction) {
            if ($this->command) {
                $this->command->warn('DemoSampleDataSeeder skipped in production.');
                $this->command->line('Set ALLOW_DEMO_SEEDING=true to run intentionally.');
            }

            return;
        }

        $this->call([
            UserSeeder::class,
            AnalyticsSeeder::class,
            InspectionSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
