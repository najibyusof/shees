<?php

namespace Database\Factories;

use App\Models\Inspection;
use App\Models\InspectionChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Inspection>
 */
class InspectionFactory extends Factory
{
    protected $model = Inspection::class;

    public function definition(): array
    {
        $performedAt = fake()->dateTimeBetween('-60 days', 'now');

        return [
            'offline_uuid' => (string) Str::uuid(),
            'inspection_checklist_id' => InspectionChecklist::query()->inRandomOrder()->value('id') ?? InspectionChecklistFactory::new(),
            'inspector_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'status' => fake()->randomElement(Inspection::STATUSES),
            'location' => fake()->randomElement(['Area A', 'Area B', 'Warehouse', 'Workshop']),
            'performed_at' => $performedAt,
            'submitted_at' => fake()->boolean(75) ? (clone $performedAt)->modify('+2 hours') : null,
            'notes' => fake()->sentence(10),
            'device_identifier' => 'device-'.fake()->numerify('###'),
            'offline_reference' => 'OFF-'.fake()->numerify('######'),
            'sync_status' => fake()->randomElement(['synced', 'pending_sync', 'conflict']),
            'sync_batch_uuid' => null,
            'last_synced_at' => now(),
        ];
    }
}
