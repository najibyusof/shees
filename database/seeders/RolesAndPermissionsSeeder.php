<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Keep RoleSeeder as the single source of truth for role + permission maps.
        $this->call(RoleSeeder::class);

        $admin = Role::query()->where('name', 'Admin')->first();
        $firstUser = User::query()->first();

        if ($admin && $firstUser) {
            $firstUser->roles()->syncWithoutDetaching([$admin->id]);
        }
    }
}
