<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentApproval>
 */
class IncidentApprovalFactory extends Factory
{
    protected $model = IncidentApproval::class;

    public function definition(): array
    {
        $decision = fake()->randomElement(['approved', 'rejected']);

        return [
            'incident_id' => Incident::query()->inRandomOrder()->value('id') ?? IncidentFactory::new(),
            'approver_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'approver_role' => fake()->randomElement(['Manager', 'Safety Officer']),
            'decision' => $decision,
            'remarks' => $decision === 'approved'
                ? fake()->sentence(8)
                : fake()->sentence(12),
            'decided_at' => fake()->dateTimeBetween('-60 days', 'now'),
        ];
    }
}
