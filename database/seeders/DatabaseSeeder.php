<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
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
        ]);
    }
}
