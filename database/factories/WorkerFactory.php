<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Worker>
 */
class WorkerFactory extends Factory
{
    protected $model = Worker::class;

    public function definition(): array
    {
        return [
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'employee_code' => 'WK-'.fake()->unique()->numerify('####'),
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'department' => fake()->randomElement(['Operations', 'Maintenance', 'Warehouse', 'QA']),
            'position' => fake()->randomElement(['Operator', 'Technician', 'Inspector', 'Packer']),
            'status' => fake()->randomElement(['active', 'inactive', 'on-leave']),
            'geofence_center_latitude' => fake()->latitude(14.4, 14.8),
            'geofence_center_longitude' => fake()->longitude(120.8, 121.2),
            'geofence_radius_meters' => fake()->numberBetween(75, 250),
            'last_latitude' => null,
            'last_longitude' => null,
            'last_seen_at' => null,
        ];
    }
}
