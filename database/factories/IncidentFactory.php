<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        return [
            'reported_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'submitted_by' => null,
            'reviewed_by' => null,
            'approved_by' => null,
            'rejected_by' => null,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(3),
            'location' => fake()->randomElement(['Warehouse A', 'Plant 1', 'Boiler Room', 'Loading Dock', 'Chemical Storage']),
            'datetime' => fake()->dateTimeBetween('-90 days', 'now'),
            'classification' => fake()->randomElement(Incident::CLASSIFICATIONS),
            'status' => fake()->randomElement(Incident::STATUSES),
            'submitted_at' => null,
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }
}
