<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SystemBaselineSeeder extends Seeder
{
    /**
     * Seed required baseline system data.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
