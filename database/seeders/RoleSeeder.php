<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Admin',
            'Manager',
            'Safety Officer',
            'Auditor',
            'Supervisor',
            'Worker',
            'HOD HSSE',
            'APSB PD',
            'MRTS',
        ];

        foreach ($roles as $roleName) {
            Role::query()->firstOrCreate(
                ['name' => $roleName],
                [
                    'slug' => Str::slug($roleName),
                    'description' => $roleName . ' role',
                ]
            );
        }
    }
}
