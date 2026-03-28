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
        $resource = $this->faker->randomElement(['users', 'reports', 'audits', 'incidents', 'workers', 'trainings']);
        $action = $this->faker->randomElement(['view', 'create', 'update', 'delete', 'approve', 'manage']);

        return [
            'name' => $this->faker->unique()->slug(2).'.'.$resource.'.'.$action,
            'description' => $this->faker->sentence(6),
        ];
    }
}
