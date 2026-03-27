<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Seeder;

class WorkerSeeder extends Seeder
{
    public function run(): void
    {
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

            $worker = Worker::query()->create([
                'user_id' => $linkedUser?->id,
                'employee_code' => 'WK-SEED-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'full_name' => $linkedUser?->name ?? fake()->name(),
                'phone' => fake()->phoneNumber(),
                'department' => fake()->randomElement(['Operations', 'Maintenance', 'Warehouse', 'Quality']),
                'position' => fake()->randomElement(['Operator', 'Technician', 'Inspector']),
                'status' => fake()->randomElement(['active', 'active', 'active', 'on-leave']),
                'geofence_center_latitude' => fake()->latitude(14.4, 14.8),
                'geofence_center_longitude' => fake()->longitude(120.8, 121.2),
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
                'latitude' => fake()->latitude(14.4, 14.8),
                'longitude' => fake()->longitude(120.8, 121.2),
                'accuracy_meters' => random_int(3, 15),
                'speed_mps' => 0,
                'heading_degrees' => null,
                'source' => fake()->randomElement(['gps', 'manual']),
                'device_identifier' => 'worker-device-'.fake()->numerify('###'),
                'external_event_id' => fake()->uuid(),
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
                    'latitude' => fake()->latitude(14.4, 14.8),
                    'longitude' => fake()->longitude(120.8, 121.2),
                    'accuracy_meters' => random_int(3, 20),
                    'speed_mps' => fake()->randomFloat(2, 0, 2),
                    'heading_degrees' => fake()->optional()->randomFloat(2, 0, 360),
                    'source' => fake()->randomElement(['gps', 'manual', 'api']),
                    'device_identifier' => 'worker-device-'.fake()->numerify('###'),
                    'external_event_id' => fake()->uuid(),
                    'inside_geofence' => $inside,
                    'distance_from_geofence_meters' => $inside ? random_int(1, 60) : random_int(120, 900),
                    'alert_level' => $inside ? null : fake()->randomElement(['medium', 'high']),
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
                    'latitude' => fake()->latitude(14.4, 14.8),
                    'longitude' => fake()->longitude(120.8, 121.2),
                    'accuracy_meters' => random_int(3, 15),
                    'speed_mps' => 0,
                    'heading_degrees' => null,
                    'source' => fake()->randomElement(['gps', 'manual']),
                    'device_identifier' => 'worker-device-'.fake()->numerify('###'),
                    'external_event_id' => fake()->uuid(),
                    'inside_geofence' => true,
                    'distance_from_geofence_meters' => random_int(1, 50),
                    'alert_level' => null,
                    'alert_message' => null,
                    'meta' => ['seeded' => true],
                ]);

                $latestLogAt = $checkoutAt;
            }

            $worker->update([
                'last_latitude' => fake()->latitude(14.4, 14.8),
                'last_longitude' => fake()->longitude(120.8, 121.2),
                'last_seen_at' => $latestLogAt,
            ]);
        }
    }
}
