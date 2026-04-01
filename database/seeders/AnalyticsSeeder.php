<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    /**
     * Seed realistic, analytics-focused demo data across modules.
     */
    public function run(): void
    {
        $this->call([
            IncidentSeeder::class,
            TrainingSeeder::class,
            AuditSeeder::class,
            WorkerSeeder::class,
        ]);
    }
}
