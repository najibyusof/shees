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
        $inside = $this->faker->boolean(80);

        return [
            'worker_id' => Worker::query()->inRandomOrder()->value('id') ?? WorkerFactory::new(),
            'recorded_by' => User::query()->inRandomOrder()->value('id'),
            'event_type' => $this->faker->randomElement(['check_in', 'check_out', 'ping', 'manual_adjustment']),
            'logged_at' => $this->faker->dateTimeBetween('-15 days', 'now'),
            'latitude' => $this->faker->latitude(14.4, 14.8),
            'longitude' => $this->faker->longitude(120.8, 121.2),
            'accuracy_meters' => $this->faker->optional()->randomFloat(2, 2, 25),
            'speed_mps' => $this->faker->optional()->randomFloat(2, 0, 5),
            'heading_degrees' => $this->faker->optional()->randomFloat(2, 0, 360),
            'source' => $this->faker->randomElement(['simulated', 'gps', 'manual', 'api']),
            'device_identifier' => 'device-'.$this->faker->numerify('###'),
            'external_event_id' => $this->faker->uuid(),
            'inside_geofence' => $inside,
            'distance_from_geofence_meters' => $inside ? $this->faker->numberBetween(0, 80) : $this->faker->numberBetween(120, 1200),
            'alert_level' => $inside ? null : $this->faker->randomElement(['medium', 'high']),
            'alert_message' => $inside ? null : 'Worker is outside configured geofence.',
            'meta' => ['seeded' => true],
        ];
    }
}
