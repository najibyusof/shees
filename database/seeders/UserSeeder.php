<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleUserBlueprint = [
            'Admin' => ['name' => 'Admin Demo', 'email' => 'admin@example.com'],
            'Manager' => ['name' => 'Manager Demo', 'email' => 'manager@example.com'],
            'Safety Officer' => ['name' => 'Safety Officer Demo', 'email' => 'safety@example.com'],
            'Auditor' => ['name' => 'Auditor Demo', 'email' => 'auditor@example.com'],
            'Supervisor' => ['name' => 'Supervisor Demo', 'email' => 'supervisor@example.com'],
            'Worker' => ['name' => 'Worker Demo', 'email' => 'worker@example.com'],
            'HOD HSSE' => ['name' => 'HOD HSSE Demo', 'email' => 'hod@example.com'],
            'APSB PD' => ['name' => 'APSB PD Demo', 'email' => 'pd@example.com'],
            'MRTS' => ['name' => 'MRTS Demo', 'email' => 'mrts@example.com'],
        ];

        foreach ($roleUserBlueprint as $roleName => $profile) {
            $role = Role::query()->where('name', $roleName)->firstOrFail();

            $user = User::query()->updateOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );

            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
