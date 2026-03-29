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
        $starts = $this->faker->dateTimeBetween('-40 days', '+10 days');
        $ends = (clone $starts)->modify('+'.$this->faker->numberBetween(1, 5).' days');
        $title = $this->faker->randomElement([
            'Confined Space Entry',
            'Hazard Communication',
            'Fire Safety Drill',
            'PPE Compliance',
            'Emergency Response Basics',
        ]).' '.$this->faker->numberBetween(1, 100);
        $description = $this->faker->paragraph(2);

        return [
            'title' => $title,
            'description' => $description,
            'title_translations' => [
                'en' => $title,
                'ms' => 'Latihan '.$this->faker->words(2, true),
            ],
            'description_translations' => [
                'en' => $description,
                'ms' => 'Kandungan latihan '.$this->faker->sentence(5),
            ],
            'starts_at' => $starts,
            'ends_at' => $ends,
            'certificate_validity_days' => $this->faker->randomElement([90, 180, 365, 730]),
            'is_active' => true,
        ];
    }
}
