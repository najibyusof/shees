<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\Training;
use App\Models\User;
use Database\Seeders\Support\SeedDataGenerator;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $faker = class_exists('Faker\\Factory')
            ? \Faker\Factory::create()
            : new SeedDataGenerator();

        $users = User::query()->get();
        if ($users->isEmpty()) {
            return;
        }

        $incidents = Incident::query()->limit(12)->get();
        $trainings = Training::query()->limit(8)->get();

        foreach ($users->random(min(18, $users->count())) as $user) {
            AuditLog::query()->create([
                'user_id' => $user->id,
                'module' => 'auth',
                'action' => 'login',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'metadata' => [
                    'description' => 'User login from seeded scenario.',
                    'ip_address' => $faker->ipv4(),
                    'seeded' => true,
                ],
                'created_at' => now()->subDays(random_int(0, 30)),
                'updated_at' => now()->subDays(random_int(0, 30)),
            ]);
        }

        foreach ($incidents as $incident) {
            AuditLog::query()->create([
                'user_id' => $incident->reported_by,
                'module' => 'incidents',
                'action' => 'create',
                'auditable_type' => Incident::class,
                'auditable_id' => $incident->id,
                'metadata' => [
                    'description' => 'Incident created by field reporter.',
                    'status' => $incident->status,
                    'seeded' => true,
                ],
                'created_at' => $incident->created_at,
                'updated_at' => $incident->created_at,
            ]);

            if ($incident->status === 'approved') {
                AuditLog::query()->create([
                    'user_id' => $incident->approved_by,
                    'module' => 'incidents',
                    'action' => 'approve',
                    'auditable_type' => Incident::class,
                    'auditable_id' => $incident->id,
                    'metadata' => [
                        'description' => 'Incident approved during workflow review.',
                        'seeded' => true,
                    ],
                    'created_at' => $incident->approved_at ?? now(),
                    'updated_at' => $incident->approved_at ?? now(),
                ]);
            }

            if ($incident->status === 'rejected') {
                AuditLog::query()->create([
                    'user_id' => $incident->rejected_by,
                    'module' => 'incidents',
                    'action' => 'reject',
                    'auditable_type' => Incident::class,
                    'auditable_id' => $incident->id,
                    'metadata' => [
                        'description' => 'Incident rejected pending corrective revision.',
                        'seeded' => true,
                    ],
                    'created_at' => $incident->rejected_at ?? now(),
                    'updated_at' => $incident->rejected_at ?? now(),
                ]);
            }
        }

        foreach ($trainings as $training) {
            $completedUsers = $training->users()
                ->wherePivot('completion_status', 'completed')
                ->limit(3)
                ->get();

            foreach ($completedUsers as $completedUser) {
                AuditLog::query()->create([
                    'user_id' => $completedUser->id,
                    'module' => 'trainings',
                    'action' => 'training_completion',
                    'auditable_type' => Training::class,
                    'auditable_id' => $training->id,
                    'metadata' => [
                        'description' => 'Training marked completed with certificate workflow.',
                        'seeded' => true,
                    ],
                    'created_at' => now()->subDays(random_int(0, 40)),
                    'updated_at' => now()->subDays(random_int(0, 40)),
                ]);
            }
        }
    }
}


