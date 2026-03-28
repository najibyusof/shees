<?php

namespace Database\Factories;

use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\InspectionResponse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InspectionResponse>
 */
class InspectionResponseFactory extends Factory
{
    protected $model = InspectionResponse::class;

    public function definition(): array
    {
        $failed = $this->faker->boolean(25);

        return [
            'inspection_id' => Inspection::query()->inRandomOrder()->value('id') ?? InspectionFactory::new(),
            'inspection_checklist_item_id' => InspectionChecklistItem::query()->inRandomOrder()->value('id') ?? InspectionChecklistItemFactory::new(),
            'offline_uuid' => (string) Str::uuid(),
            'response_value' => $failed ? 'failed' : 'passed',
            'response_meta' => ['seeded' => true],
            'is_non_compliant' => $failed,
            'comment' => $failed ? $this->faker->sentence(10) : $this->faker->optional()->sentence(6),
            'sync_status' => $this->faker->randomElement(['synced', 'pending_sync']),
            'sync_batch_uuid' => null,
            'last_synced_at' => now(),
        ];
    }
}
