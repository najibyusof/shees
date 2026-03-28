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
        $seeders = [
            PermissionSeeder::class,
            RolesAndPermissionsSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            IncidentSeeder::class,
            TrainingSeeder::class,
            InspectionSeeder::class,
            AuditSeeder::class,
            WorkerSeeder::class,
            AuditLogSeeder::class,
        ];

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
