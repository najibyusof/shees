<?php

namespace Database\Factories;

use App\Models\InspectionChecklist;
use App\Models\InspectionChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InspectionChecklistItem>
 */
class InspectionChecklistItemFactory extends Factory
{
    protected $model = InspectionChecklistItem::class;

    public function definition(): array
    {
        $itemType = fake()->randomElement(InspectionChecklistItem::ITEM_TYPES);

        return [
            'inspection_checklist_id' => InspectionChecklist::query()->inRandomOrder()->value('id') ?? InspectionChecklistFactory::new(),
            'offline_uuid' => (string) Str::uuid(),
            'label' => fake()->sentence(4),
            'label_translations' => null,
            'item_type' => $itemType,
            'options' => $itemType === 'choice' ? ['Pass', 'Fail', 'N/A'] : null,
            'is_required' => true,
            'sort_order' => fake()->numberBetween(1, 20),
            'sync_status' => 'synced',
            'sync_batch_uuid' => null,
            'last_synced_at' => now(),
        ];
    }
}
