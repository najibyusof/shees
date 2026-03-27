<?php

namespace Database\Factories;

use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    protected $model = Training::class;

    public function definition(): array
    {
        $starts = fake()->dateTimeBetween('-40 days', '+10 days');
        $ends = (clone $starts)->modify('+'.fake()->numberBetween(1, 5).' days');

        return [
            'title' => fake()->randomElement([
                'Confined Space Entry',
                'Hazard Communication',
                'Fire Safety Drill',
                'PPE Compliance',
                'Emergency Response Basics',
            ]).' '.fake()->numberBetween(1, 100),
            'description' => fake()->paragraph(2),
            'title_translations' => null,
            'description_translations' => null,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'certificate_validity_days' => fake()->randomElement([90, 180, 365, 730]),
            'is_active' => true,
        ];
    }
}
