<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use Database\Seeders\Support\SeedDataGenerator;
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

        $trainingTitles = [
            'Confined Space Entry',
            'Hazard Communication',
            'Fire Safety Drill',
            'PPE Compliance',
            'Emergency Response Basics',
        ];

        for ($i = 0; $i < 10; $i++) {
            $startsAt = now()->subDays(random_int(5, 40));
            $endsAt = (clone $startsAt)->addDays(random_int(1, 5));
            $title = $faker->randomElement($trainingTitles).' '.$faker->numerify('##');
            $description = $faker->sentence(14);

            $training = Training::query()->create([
                'title' => $title,
                'description' => $description,
                'title_translations' => [
                    'en' => $title,
                    'ms' => 'Latihan '.$faker->words(2, true),
                ],
                'description_translations' => [
                    'en' => $description,
                    'ms' => 'Modul latihan '.$faker->sentence(6),
                ],
                'starts_at' => $startsAt->toDateString(),
                'ends_at' => $endsAt->toDateString(),
                'certificate_validity_days' => $faker->randomElement([90, 180, 365, 730]),
                'is_active' => true,
            ]);
            $assignedUsers = $users->random(random_int(5, 10));
            $assignedBy = $users->random();

            foreach ($assignedUsers as $assignee) {
                $roll = random_int(1, 100);
                $completionStatus = $roll <= 50 ? 'completed' : ($roll <= 80 ? 'assigned' : 'pending');
                $assignedAt = now()->subDays(random_int(1, 120));
                $completedAt = $completionStatus === 'completed' ? (clone $assignedAt)->addDays(random_int(1, 14)) : null;

                $training->users()->syncWithoutDetaching([
                    $assignee->id => [
                        'assigned_by' => $assignedBy->id,
                        'assigned_at' => $assignedAt,
                        'completed_at' => $completedAt,
                        'completion_status' => $completionStatus,
                        'expiry_notified_at' => null,
                    ],
                ]);

                if ($completionStatus !== 'completed') {
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
                    'file_path' => 'certificates/'.Str::slug($training->title).'-'.$faker->uuid().'.pdf',
                    'original_name' => Str::slug($training->title).'-certificate.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => random_int(120_000, 900_000),
                    'issued_at' => $issuedAt->toDateString(),
                    'expires_at' => $expiresAt->toDateString(),
                    'expiry_notified_at' => $isExpired ? now()->subDays(random_int(1, 30)) : null,
                    'metadata' => [
                        'completion_status' => 'completed',
                        'source' => 'training_seed',
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
                    'file_path' => 'certificates/expired-worker-'.$workerUser->id.'-'.$faker->uuid().'.pdf',
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
                'title_translations' => [
                    'en' => 'Overdue Mandatory Training '.$i,
                    'ms' => 'Latihan Mandatori Tertunggak '.$i,
                ],
                'description_translations' => [
                    'en' => 'Seeded overdue training scenario for compliance follow-up.',
                    'ms' => 'Senario latihan tertunggak untuk susulan pematuhan.',
                ],
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

        // Scenario set: upcoming trainings with active assignments and no completion yet.
        for ($i = 1; $i <= 2; $i++) {
            $upcoming = Training::query()->create([
                'title' => 'Upcoming Safety Drill '.$i,
                'description' => 'Scheduled future training to validate planning workflows.',
                'title_translations' => [
                    'en' => 'Upcoming Safety Drill '.$i,
                    'ms' => 'Latihan Keselamatan Akan Datang '.$i,
                ],
                'description_translations' => [
                    'en' => 'Scheduled future training to validate planning workflows.',
                    'ms' => 'Latihan masa hadapan untuk mengesahkan aliran perancangan.',
                ],
                'starts_at' => now()->addDays(7 + ($i * 3))->toDateString(),
                'ends_at' => now()->addDays(9 + ($i * 3))->toDateString(),
                'certificate_validity_days' => 365,
                'is_active' => true,
            ]);

            $assignedBy = $users->random();
            foreach ($users->random(random_int(4, 6)) as $assignee) {
                $upcoming->users()->syncWithoutDetaching([
                    $assignee->id => [
                        'assigned_by' => $assignedBy->id,
                        'assigned_at' => now()->subDays(random_int(0, 5)),
                        'completed_at' => null,
                        'completion_status' => 'assigned',
                        'expiry_notified_at' => null,
                    ],
                ]);
            }
        }
    }
}


