<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\Worker;
use Carbon\CarbonPeriod;
use Database\Seeders\Support\SeedDataGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
                    'temporary_id' => (string) Str::uuid(),
                    'local_created_at' => (clone $baseDate)->subMinutes(5),
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
                    'temporary_id' => (string) Str::uuid(),
                    'local_created_at' => (clone $loggedAt)->subMinutes(2),
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
                    'temporary_id' => (string) Str::uuid(),
                    'local_created_at' => (clone $checkoutAt)->subMinutes(2),
                ]);

                $latestLogAt = $checkoutAt;
            }

            $worker->update([
                'last_latitude' => $faker->latitude(14.4, 14.8),
                'last_longitude' => $faker->longitude(120.8, 121.2),
                'last_seen_at' => $latestLogAt,
            ]);
        }

        // Scenario set: 3 high-risk workers with repeated geofence breaches.
        for ($i = 1; $i <= 3; $i++) {
            $employeeCode = 'WK-RISK-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            if (Worker::query()->where('employee_code', $employeeCode)->exists()) {
                continue;
            }

            $worker = Worker::query()->create([
                'user_id' => $workerUsers->isNotEmpty() ? $workerUsers->random()->id : null,
                'employee_code' => $employeeCode,
                'full_name' => $faker->name(),
                'phone' => $faker->phoneNumber(),
                'department' => 'Operations',
                'position' => 'Field Technician',
                'status' => 'active',
                'geofence_center_latitude' => $faker->latitude(14.4, 14.8),
                'geofence_center_longitude' => $faker->longitude(120.8, 121.2),
                'geofence_radius_meters' => random_int(70, 100),
                'last_latitude' => null,
                'last_longitude' => null,
                'last_seen_at' => null,
            ]);

            $start = now()->subDays(random_int(1, 5))->startOfDay()->addHours(8);
            $latestLogAt = $start;

            for ($j = 0; $j < 8; $j++) {
                $loggedAt = (clone $start)->addMinutes($j * 45);
                $inside = $j < 2 ? true : random_int(1, 100) <= 35;

                AttendanceLog::query()->create([
                    'worker_id' => $worker->id,
                    'recorded_by' => $recorders->random()->id,
                    'event_type' => $j === 0 ? 'check_in' : 'ping',
                    'logged_at' => $loggedAt,
                    'latitude' => $faker->latitude(14.4, 14.8),
                    'longitude' => $faker->longitude(120.8, 121.2),
                    'accuracy_meters' => random_int(5, 20),
                    'speed_mps' => $faker->randomFloat(2, 0, 3),
                    'heading_degrees' => $faker->optional()->randomFloat(2, 0, 360),
                    'source' => 'simulated',
                    'device_identifier' => 'risk-worker-device-'.$faker->numerify('###'),
                    'external_event_id' => $faker->uuid(),
                    'inside_geofence' => $inside,
                    'distance_from_geofence_meters' => $inside ? random_int(1, 60) : random_int(150, 1200),
                    'alert_level' => $inside ? null : $faker->randomElement(['high', 'critical']),
                    'alert_message' => $inside ? null : 'Repeated geofence breach detected.',
                    'meta' => ['seeded' => true, 'scenario' => 'high_risk_geofence_worker'],
                    'temporary_id' => (string) Str::uuid(),
                    'local_created_at' => (clone $loggedAt)->subMinutes(1),
                ]);

                $latestLogAt = $loggedAt;
            }

            $worker->update([
                'last_latitude' => $faker->latitude(14.4, 14.8),
                'last_longitude' => $faker->longitude(120.8, 121.2),
                'last_seen_at' => $latestLogAt,
            ]);
        }

        $this->seedSixMonthAttendancePatterns($recorders, $faker);
    }

    private function seedSixMonthAttendancePatterns($recorders, mixed $faker): void
    {
        $workers = Worker::query()->limit(35)->get();
        if ($workers->isEmpty()) {
            return;
        }

        $period = CarbonPeriod::create(now()->subMonths(6)->startOfDay(), '1 day', now()->subDay()->endOfDay());

        foreach ($workers as $worker) {
            $activeBias = $worker->status === 'active' ? 0.9 : ($worker->status === 'on-leave' ? 0.25 : 0.5);

            foreach ($period as $day) {
                $isWeekend = in_array($day->dayOfWeekIso, [6, 7], true);
                $baseChance = $isWeekend ? 0.28 : 0.88;

                if (mt_rand(1, 1000) > (int) round($baseChance * $activeBias * 1000)) {
                    continue;
                }

                $checkIn = $day->copy()->setTime(random_int(7, 9), random_int(0, 45));
                $checkOut = $checkIn->copy()->addHours(random_int(8, 11))->addMinutes(random_int(0, 40));
                $hasCheckout = mt_rand(1, 100) > 10;

                AttendanceLog::factory()
                    ->count($hasCheckout ? 2 : 1)
                    ->state(fn () => [
                        'worker_id' => $worker->id,
                        'recorded_by' => $recorders->random()->id,
                        'source' => 'simulated',
                        'inside_geofence' => true,
                        'alert_level' => null,
                        'alert_message' => null,
                        'meta' => ['seeded' => true, 'pattern' => '6_month_attendance'],
                        'temporary_id' => (string) Str::uuid(),
                        'local_created_at' => $checkIn,
                    ])
                    ->sequence(
                        [
                            'event_type' => 'check_in',
                            'logged_at' => $checkIn,
                            'distance_from_geofence_meters' => random_int(1, 55),
                        ],
                        [
                            'event_type' => 'check_out',
                            'logged_at' => $checkOut,
                            'distance_from_geofence_meters' => random_int(1, 60),
                        ]
                    )
                    ->create();

                if (mt_rand(1, 100) <= 18) {
                    $breachTime = $checkIn->copy()->addHours(random_int(1, 6));
                    AttendanceLog::factory()
                        ->state(fn () => [
                            'worker_id' => $worker->id,
                            'recorded_by' => $recorders->random()->id,
                            'event_type' => 'ping',
                            'logged_at' => $breachTime,
                            'source' => 'simulated',
                            'inside_geofence' => false,
                            'distance_from_geofence_meters' => random_int(130, 950),
                            'alert_level' => random_int(1, 100) <= 30 ? 'high' : 'medium',
                            'alert_message' => 'Worker outside geofence boundary.',
                            'meta' => ['seeded' => true, 'pattern' => 'weekday_spike'],
                            'temporary_id' => (string) Str::uuid(),
                            'local_created_at' => $breachTime,
                        ])
                        ->create();
                }

                $worker->update([
                    'last_seen_at' => $hasCheckout ? $checkOut : $checkIn,
                    'last_latitude' => $faker->latitude(14.4, 14.8),
                    'last_longitude' => $faker->longitude(120.8, 121.2),
                ]);
            }
        }
    }
}


