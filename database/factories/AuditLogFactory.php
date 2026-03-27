<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'action' => fake()->randomElement(['login', 'create', 'approve', 'reject', 'training_completion']),
            'module' => fake()->randomElement(['auth', 'incidents', 'audits', 'trainings']),
            'auditable_type' => null,
            'auditable_id' => null,
            'metadata' => [
                'description' => fake()->sentence(8),
                'seeded' => true,
            ],
        ];
    }
}
