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
        $performedAt = $this->faker->dateTimeBetween('-60 days', 'now');

        return [
            'offline_uuid' => (string) Str::uuid(),
            'inspection_checklist_id' => InspectionChecklist::query()->inRandomOrder()->value('id') ?? InspectionChecklistFactory::new(),
            'inspector_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'status' => $this->faker->randomElement(Inspection::STATUSES),
            'location' => $this->faker->randomElement(['Area A', 'Area B', 'Warehouse', 'Workshop']),
            'performed_at' => $performedAt,
            'submitted_at' => $this->faker->boolean(75) ? (clone $performedAt)->modify('+2 hours') : null,
            'notes' => $this->faker->sentence(10),
            'device_identifier' => 'device-'.$this->faker->numerify('###'),
            'offline_reference' => 'OFF-'.$this->faker->numerify('######'),
            'sync_status' => $this->faker->randomElement(['synced', 'pending_sync', 'conflict']),
            'sync_batch_uuid' => null,
            'last_synced_at' => now(),
        ];
    }
}
