<?php

namespace Database\Factories;

use App\Models\InspectionChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InspectionChecklist>
 */
class InspectionChecklistFactory extends Factory
{
    protected $model = InspectionChecklist::class;

    public function definition(): array
    {
        return [
            'offline_uuid' => (string) Str::uuid(),
            'code' => 'CHK-'.$this->faker->unique()->numerify('###'),
            'title' => $this->faker->randomElement([
                'Daily Safety Walkthrough',
                'Warehouse Readiness',
                'Chemical Storage Compliance',
                'Work Permit Verification',
            ]),
            'description' => $this->faker->sentence(10),
            'title_translations' => null,
            'description_translations' => null,
            'version' => $this->faker->numberBetween(1, 3),
            'is_active' => true,
            'sync_status' => 'synced',
            'sync_batch_uuid' => null,
            'last_synced_at' => now(),
            'created_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
        ];
    }
}
