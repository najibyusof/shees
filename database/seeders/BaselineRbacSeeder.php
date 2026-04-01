<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaselineRbacSeeder extends Seeder
{
    /**
     * Seed baseline RBAC data required by the system.
     */
    public function run(): void
    {
        $this->call(SystemBaselineSeeder::class);
    }
}
