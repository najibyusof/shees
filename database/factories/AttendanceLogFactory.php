<?php

namespace Database\Factories;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceLog>
 */
class AttendanceLogFactory extends Factory
{
    protected $model = AttendanceLog::class;

    public function definition(): array
    {
        $inside = fake()->boolean(80);

        return [
            'worker_id' => Worker::query()->inRandomOrder()->value('id') ?? WorkerFactory::new(),
            'recorded_by' => User::query()->inRandomOrder()->value('id'),
            'event_type' => fake()->randomElement(['check_in', 'check_out', 'ping', 'manual_adjustment']),
            'logged_at' => fake()->dateTimeBetween('-15 days', 'now'),
            'latitude' => fake()->latitude(14.4, 14.8),
            'longitude' => fake()->longitude(120.8, 121.2),
            'accuracy_meters' => fake()->optional()->randomFloat(2, 2, 25),
            'speed_mps' => fake()->optional()->randomFloat(2, 0, 5),
            'heading_degrees' => fake()->optional()->randomFloat(2, 0, 360),
            'source' => fake()->randomElement(['simulated', 'gps', 'manual', 'api']),
            'device_identifier' => 'device-'.fake()->numerify('###'),
            'external_event_id' => fake()->uuid(),
            'inside_geofence' => $inside,
            'distance_from_geofence_meters' => $inside ? fake()->numberBetween(0, 80) : fake()->numberBetween(120, 1200),
            'alert_level' => $inside ? null : fake()->randomElement(['medium', 'high']),
            'alert_message' => $inside ? null : 'Worker is outside configured geofence.',
            'meta' => ['seeded' => true],
        ];
    }
}
