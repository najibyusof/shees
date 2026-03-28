<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentComment>
 */
class IncidentCommentFactory extends Factory
{
    protected $model = IncidentComment::class;

    public function definition(): array
    {
        return [
            'incident_id' => Incident::query()->inRandomOrder()->value('id') ?? IncidentFactory::new(),
            'user_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'comment' => $this->faker->sentence(12),
        ];
    }
}
