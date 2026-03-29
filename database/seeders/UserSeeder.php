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
            'Admin' => [
                ['name' => 'Super Admin', 'email' => 'superadmin@shees.local'],
                ['name' => 'Nina Delgado', 'email' => 'nina.delgado@shees.local'],
                ['name' => 'Marcus Lee', 'email' => 'marcus.lee@shees.local'],
            ],
            'Manager' => [
                ['name' => 'Olivia Hart', 'email' => 'olivia.hart@shees.local'],
                ['name' => 'Daniel Cruz', 'email' => 'daniel.cruz@shees.local'],
                ['name' => 'Ivy Ramos', 'email' => 'ivy.ramos@shees.local'],
                ['name' => 'Robert King', 'email' => 'robert.king@shees.local'],
            ],
            'Safety Officer' => [
                ['name' => 'Sophia Tan', 'email' => 'sophia.tan@shees.local'],
                ['name' => 'Harvey Quinn', 'email' => 'harvey.quinn@shees.local'],
                ['name' => 'Elaine Yu', 'email' => 'elaine.yu@shees.local'],
                ['name' => 'Miguel Santos', 'email' => 'miguel.santos@shees.local'],
            ],
            'Auditor' => [
                ['name' => 'Priya Menon', 'email' => 'priya.menon@shees.local'],
                ['name' => 'Leo Park', 'email' => 'leo.park@shees.local'],
                ['name' => 'Grace Lim', 'email' => 'grace.lim@shees.local'],
            ],
            'Supervisor' => [
                ['name' => 'Noah Rivera', 'email' => 'noah.rivera@shees.local'],
                ['name' => 'Alicia Gomez', 'email' => 'alicia.gomez@shees.local'],
                ['name' => 'Trevor Hall', 'email' => 'trevor.hall@shees.local'],
                ['name' => 'Bianca Flores', 'email' => 'bianca.flores@shees.local'],
            ],
            'Worker' => [
                ['name' => 'Jose Villanueva', 'email' => 'jose.villanueva@shees.local'],
                ['name' => 'Camila Reyes', 'email' => 'camila.reyes@shees.local'],
                ['name' => 'Ethan Brooks', 'email' => 'ethan.brooks@shees.local'],
                ['name' => 'Lara Stone', 'email' => 'lara.stone@shees.local'],
                ['name' => 'Pablo Ortiz', 'email' => 'pablo.ortiz@shees.local'],
            ],

                // ── Incident workflow roles ──────────────────────────────────
                'HOD HSSE' => [
                    ['name' => 'Fatima Al-Rashid', 'email' => 'fatima.alrashid@shees.local'],
                    ['name' => 'Raymond Ong', 'email' => 'raymond.ong@shees.local'],
                ],

                'APSB PD' => [
                    ['name' => 'Samuel Adeyemi', 'email' => 'samuel.adeyemi@shees.local'],
                    ['name' => 'Claudia Ng', 'email' => 'claudia.ng@shees.local'],
                ],

                'MRTS' => [
                    ['name' => 'Jonathan Bautista', 'email' => 'jonathan.bautista@shees.local'],
                    ['name' => 'Mei Ling Tan', 'email' => 'meling.tan@shees.local'],
                ],
        ];

        foreach ($roleUserBlueprint as $roleName => $users) {
            $role = Role::query()->where('name', $roleName)->firstOrFail();

            foreach ($users as $profile) {
                $user = User::query()->firstOrCreate(
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
}
