<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use Database\Seeders\Support\SeedDataGenerator;
use Database\Factories\TrainingFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        $faker = class_exists('Faker\\Factory')
            ? \Faker\Factory::create()
            : new SeedDataGenerator();

        $users = User::query()->get();
        $workerUsers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Worker'))
            ->get();

        if ($users->count() < 10) {
            return;
        }

        for ($i = 0; $i < 10; $i++) {
            $training = TrainingFactory::new()->create();
            $assignedUsers = $users->random(random_int(5, 10));
            $assignedBy = $users->random();

            foreach ($assignedUsers as $assignee) {
                $isCompleted = random_int(1, 100) <= 60;
                $assignedAt = now()->subDays(random_int(1, 120));
                $completedAt = $isCompleted ? (clone $assignedAt)->addDays(random_int(1, 14)) : null;

                $training->users()->syncWithoutDetaching([
                    $assignee->id => [
                        'assigned_by' => $assignedBy->id,
                        'assigned_at' => $assignedAt,
                        'completed_at' => $completedAt,
                        'completion_status' => $isCompleted ? 'completed' : 'pending',
                        'expiry_notified_at' => null,
                    ],
                ]);

                if (! $isCompleted) {
                    continue;
                }

                $issuedAt = now()->subDays(random_int(30, 540));
                $isExpired = random_int(1, 100) <= 30;
                $expiresAt = $isExpired
                    ? now()->subDays(random_int(1, 120))
                    : now()->addDays(random_int(15, 365));

                Certificate::query()->create([
                    'training_id' => $training->id,
                    'user_id' => $assignee->id,
                    'uploaded_by' => $assignedBy->id,
                    'file_path' => 'certificates/'.Str::slug($training->title).'-'.$this->faker->uuid().'.pdf',
                    'original_name' => Str::slug($training->title).'-certificate.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => random_int(120_000, 900_000),
                    'issued_at' => $issuedAt->toDateString(),
                    'expires_at' => $expiresAt->toDateString(),
                    'expiry_notified_at' => $isExpired ? now()->subDays(random_int(1, 30)) : null,
                    'metadata' => [
                        'completion_status' => 'completed',
                        'seeded' => true,
                    ],
                ]);
            }
        }

        // Scenario set: ensure 5 workers have expired certificates.
        if ($workerUsers->count() >= 5) {
            $expiredTraining = Training::query()->create([
                'title' => 'Regulatory Refresher (Expired Certification Batch)',
                'description' => 'Seeded scenario to represent workers with expired certifications.',
                'title_translations' => null,
                'description_translations' => null,
                'starts_at' => now()->subMonths(8)->toDateString(),
                'ends_at' => now()->subMonths(8)->addDays(2)->toDateString(),
                'certificate_validity_days' => 180,
                'is_active' => true,
            ]);

            $assignedBy = $users->random();
            foreach ($workerUsers->take(5) as $workerUser) {
                $assignedAt = now()->subMonths(8);
                $completedAt = (clone $assignedAt)->addDays(random_int(2, 7));
                $issuedAt = (clone $completedAt)->addDay();

                $expiredTraining->users()->syncWithoutDetaching([
                    $workerUser->id => [
                        'assigned_by' => $assignedBy->id,
                        'assigned_at' => $assignedAt,
                        'completed_at' => $completedAt,
                        'completion_status' => 'completed',
                        'expiry_notified_at' => now()->subDays(random_int(3, 20)),
                    ],
                ]);

                Certificate::query()->create([
                    'training_id' => $expiredTraining->id,
                    'user_id' => $workerUser->id,
                    'uploaded_by' => $assignedBy->id,
                    'file_path' => 'certificates/expired-worker-'.$workerUser->id.'-'.$this->faker->uuid().'.pdf',
                    'original_name' => 'expired-worker-'.$workerUser->id.'-certificate.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => random_int(120_000, 900_000),
                    'issued_at' => $issuedAt->toDateString(),
                    'expires_at' => now()->subDays(random_int(10, 120))->toDateString(),
                    'expiry_notified_at' => now()->subDays(random_int(1, 20)),
                    'metadata' => [
                        'completion_status' => 'completed',
                        'scenario' => 'expired_worker_certificate',
                        'seeded' => true,
                    ],
                ]);
            }
        }

        // Scenario set: 3 overdue trainings with pending assignments past due end date.
        for ($i = 1; $i <= 3; $i++) {
            $overdueTraining = Training::query()->create([
                'title' => 'Overdue Mandatory Training '.$i,
                'description' => 'Seeded overdue training scenario for compliance follow-up.',
                'title_translations' => null,
                'description_translations' => null,
                'starts_at' => now()->subDays(40 + ($i * 5))->toDateString(),
                'ends_at' => now()->subDays(20 + ($i * 3))->toDateString(),
                'certificate_validity_days' => 365,
                'is_active' => true,
            ]);

            $assignees = $users->random(random_int(5, 8));
            $assignedBy = $users->random();

            foreach ($assignees as $assignee) {
                $assignedAt = now()->subDays(random_int(30, 55));

                $overdueTraining->users()->syncWithoutDetaching([
                    $assignee->id => [
                        'assigned_by' => $assignedBy->id,
                        'assigned_at' => $assignedAt,
                        'completed_at' => null,
                        'completion_status' => 'pending',
                        'expiry_notified_at' => null,
                    ],
                ]);
            }
        }
    }
}
