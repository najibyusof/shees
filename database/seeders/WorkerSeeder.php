<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\Support\SeedDataGenerator;
use Illuminate\Database\Seeder;

class WorkerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = class_exists('Faker\\Factory')
            ? \Faker\Factory::create()
            : new SeedDataGenerator();

        $workerUsers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Worker'))
            ->get();

        $recorders = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Supervisor', 'Safety Officer', 'Manager']))
            ->get();

        if ($recorders->isEmpty()) {
            return;
        }

        for ($i = 1; $i <= 20; $i++) {
            $linkedUser = $workerUsers->isNotEmpty() && $i <= $workerUsers->count() ? $workerUsers[$i - 1] : null;
            $employeeCode = 'WK-SEED-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            if (Worker::query()->where('employee_code', $employeeCode)->exists()) {
                continue;
            }

            $worker = Worker::query()->create([
                'user_id' => $linkedUser?->id,
                'employee_code' => $employeeCode,
                'full_name' => $linkedUser?->name ?? $faker->name(),
                'phone' => $faker->phoneNumber(),
                'department' => $faker->randomElement(['Operations', 'Maintenance', 'Warehouse', 'Quality']),
                'position' => $faker->randomElement(['Operator', 'Technician', 'Inspector']),
                'status' => $faker->randomElement(['active', 'active', 'active', 'on-leave']),
                'geofence_center_latitude' => $faker->latitude(14.4, 14.8),
                'geofence_center_longitude' => $faker->longitude(120.8, 121.2),
                'geofence_radius_meters' => random_int(80, 180),
                'last_latitude' => null,
                'last_longitude' => null,
                'last_seen_at' => null,
            ]);

            $logCount = random_int(5, 10);
            $missingCheckout = random_int(1, 100) <= 35;
            $baseDate = now()->subDays(random_int(1, 14))->startOfDay()->addHours(7);
            $latestLogAt = null;

            AttendanceLog::query()->create([
                'worker_id' => $worker->id,
                'recorded_by' => $recorders->random()->id,
                'event_type' => 'check_in',
                'logged_at' => $baseDate,
                'latitude' => $faker->latitude(14.4, 14.8),
                'longitude' => $faker->longitude(120.8, 121.2),
                'accuracy_meters' => random_int(3, 15),
                'speed_mps' => 0,
                'heading_degrees' => null,
                'source' => $faker->randomElement(['gps', 'manual']),
                'device_identifier' => 'worker-device-'.$faker->numerify('###'),
                'external_event_id' => $faker->uuid(),
                'inside_geofence' => true,
                'distance_from_geofence_meters' => random_int(1, 45),
                'alert_level' => null,
                'alert_message' => null,
                'meta' => ['seeded' => true],
            ]);
            $latestLogAt = $baseDate;

            for ($j = 1; $j < $logCount; $j++) {
                $loggedAt = (clone $baseDate)->addMinutes($j * random_int(30, 90));
                $inside = random_int(1, 100) <= 80;

                AttendanceLog::query()->create([
                    'worker_id' => $worker->id,
                    'recorded_by' => $recorders->random()->id,
                    'event_type' => 'ping',
                    'logged_at' => $loggedAt,
                    'latitude' => $faker->latitude(14.4, 14.8),
                    'longitude' => $faker->longitude(120.8, 121.2),
                    'accuracy_meters' => random_int(3, 20),
                    'speed_mps' => $faker->randomFloat(2, 0, 2),
                    'heading_degrees' => $faker->optional()->randomFloat(2, 0, 360),
                    'source' => $faker->randomElement(['gps', 'manual', 'api']),
                    'device_identifier' => 'worker-device-'.$faker->numerify('###'),
                    'external_event_id' => $faker->uuid(),
                    'inside_geofence' => $inside,
                    'distance_from_geofence_meters' => $inside ? random_int(1, 60) : random_int(120, 900),
                    'alert_level' => $inside ? null : $faker->randomElement(['medium', 'high']),
                    'alert_message' => $inside ? null : 'Worker outside geofence boundary.',
                    'meta' => ['seeded' => true],
                ]);

                $latestLogAt = $loggedAt;
            }

            if (! $missingCheckout) {
                $checkoutAt = (clone $baseDate)->addHours(9);

                AttendanceLog::query()->create([
                    'worker_id' => $worker->id,
                    'recorded_by' => $recorders->random()->id,
                    'event_type' => 'check_out',
                    'logged_at' => $checkoutAt,
                    'latitude' => $faker->latitude(14.4, 14.8),
                    'longitude' => $faker->longitude(120.8, 121.2),
                    'accuracy_meters' => random_int(3, 15),
                    'speed_mps' => 0,
                    'heading_degrees' => null,
                    'source' => $faker->randomElement(['gps', 'manual']),
                    'device_identifier' => 'worker-device-'.$faker->numerify('###'),
                    'external_event_id' => $faker->uuid(),
                    'inside_geofence' => true,
                    'distance_from_geofence_meters' => random_int(1, 50),
                    'alert_level' => null,
                    'alert_message' => null,
                    'meta' => ['seeded' => true],
                ]);

                $latestLogAt = $checkoutAt;
            }

            $worker->update([
                'last_latitude' => $faker->latitude(14.4, 14.8),
                'last_longitude' => $faker->longitude(120.8, 121.2),
                'last_seen_at' => $latestLogAt,
            ]);
        }
    }
}


