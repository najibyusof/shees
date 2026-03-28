<?php

namespace Database\Factories;

use App\Models\CorrectiveAction;
use App\Models\NcrReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorrectiveAction>
 */
class CorrectiveActionFactory extends Factory
{
    protected $model = CorrectiveAction::class;

    public function definition(): array
    {
        return [
            'ncr_report_id' => NcrReport::query()->inRandomOrder()->value('id') ?? NcrReportFactory::new(),
            'assigned_to' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'verified_by' => null,
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(2),
            'status' => $this->faker->randomElement(['open', 'in_progress', 'completed', 'verified']),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'completed_at' => null,
            'verified_at' => null,
            'completion_notes' => $this->faker->optional()->sentence(10),
        ];
    }
}
