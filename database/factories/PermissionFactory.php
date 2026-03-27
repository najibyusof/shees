<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $resource = fake()->randomElement(['users', 'reports', 'audits', 'incidents', 'workers', 'trainings']);
        $action = fake()->randomElement(['view', 'create', 'update', 'delete', 'approve', 'manage']);

        return [
            'name' => fake()->unique()->slug(2).'.'.$resource.'.'.$action,
            'description' => fake()->sentence(6),
        ];
    }
}
